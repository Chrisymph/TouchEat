<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\SMSTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ClientController extends Controller
{
    public function dashboard()
    {
        if (!Auth::check() || Auth::user()->role !== 'client') {
            return redirect()->route('client.auth');
        }

        $user = Auth::user();
        if ($user->isSuspended()) {
            Auth::logout();
            return redirect()->route('client.auth')->with('error', 'Votre compte a été suspendu. Veuillez contacter l\'administrateur.');
        }

        $menuItems = MenuItem::where('available', true)->get();
        $currentOrder = Order::where('table_number', Auth::user()->table_number)
            ->whereIn('status', ['commandé', 'en_cours', 'prêt'])
            ->with('items.menuItem')
            ->first();

        $cart = session()->get('cart', []);
        $cartItems = array_values($cart);
        $cartCount = array_sum(array_column($cart, 'quantity'));

        return view('client.dashboard', [
            'tableNumber' => Auth::user()->table_number,
            'menuItems' => $menuItems,
            'currentOrder' => $currentOrder,
            'cartItems' => $cartItems,
            'cartCount' => $cartCount
        ]);
    }

    public function addToCart(Request $request)
    {
        $user = Auth::user();
        if ($user->isSuspended()) {
            return response()->json([
                'success' => false, 
                'message' => 'Votre compte a été suspendu. Vous ne pouvez pas passer de commande.'
            ], 403);
        }

        $request->validate([
            'menu_item_id' => 'required|exists:menu_items,id',
            'quantity' => 'required|integer|min:1',
            'order_id' => 'nullable|exists:orders,id'
        ]);

        $cart = session()->get('cart', []);
        $menuItem = MenuItem::find($request->menu_item_id);

        if (isset($cart[$request->menu_item_id])) {
            $cart[$request->menu_item_id]['quantity'] += $request->quantity;
        } else {
            $cart[$request->menu_item_id] = [
                'id' => $menuItem->id,
                'name' => $menuItem->name,
                'description' => $menuItem->description,
                'price' => $menuItem->price,
                'quantity' => $request->quantity,
                'category' => $menuItem->category,
                'promotion_discount' => $menuItem->promotion_discount,
                'original_price' => $menuItem->original_price,
                'order_id' => $request->order_id
            ];
        }

        session()->put('cart', $cart);

        $cartCount = array_sum(array_column($cart, 'quantity'));

        return response()->json([
            'success' => true,
            'cart_count' => $cartCount,
            'cart_items' => array_values($cart),
            'has_existing_order' => !empty($request->order_id)
        ]);
    }

    public function updateCart(Request $request)
    {
        $user = Auth::user();
        if ($user->isSuspended()) {
            return response()->json([
                'success' => false, 
                'message' => 'Votre compte a été suspendu. Vous ne pouvez pas modifier votre panier.'
            ], 403);
        }

        $request->validate([
            'menu_item_id' => 'required|exists:menu_items,id',
            'quantity' => 'required|integer|min:0'
        ]);

        $cart = session()->get('cart', []);

        if ($request->quantity == 0) {
            unset($cart[$request->menu_item_id]);
        } else {
            if (isset($cart[$request->menu_item_id])) {
                $cart[$request->menu_item_id]['quantity'] = $request->quantity;
            }
        }

        session()->put('cart', $cart);

        $cartCount = array_sum(array_column($cart, 'quantity'));
        $cartTotal = 0;
        foreach ($cart as $item) {
            $cartTotal += $item['price'] * $item['quantity'];
        }

        return response()->json([
            'success' => true,
            'cart_count' => $cartCount,
            'cart_total' => $cartTotal,
            'cart_items' => array_values($cart)
        ]);
    }

    public function placeOrder(Request $request)
    {
        $user = Auth::user();
        if ($user->isSuspended()) {
            return response()->json([
                'success' => false, 
                'message' => 'Votre compte a été suspendu. Vous ne pouvez pas passer de commande.'
            ], 403);
        }

        $request->validate([
            'order_type' => 'required|in:sur_place,livraison',
            'phone_number' => 'required|string',
            'network' => 'required_if:order_type,sur_place|in:mtn,moov,orange',
            'existing_order_id' => 'nullable|exists:orders,id',
            'delivery_address' => 'required_if:order_type,livraison|string|max:255',
            'delivery_notes' => 'nullable|string|max:500'
        ]);

        $cart = session()->get('cart', []);
        
        if (empty($cart)) {
            return response()->json([
                'success' => false, 
                'message' => 'Votre panier est vide'
            ]);
        }

        $total = 0;
        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        if ($request->order_type === 'sur_place' && $request->has('network')) {
            session()->put('selected_network', $request->network);
        }

        if ($request->has('existing_order_id') && $request->existing_order_id) {
            $order = Order::where('id', $request->existing_order_id)
                ->where('table_number', Auth::user()->table_number)
                ->firstOrFail();

            foreach ($cart as $menuItemId => $item) {
                $existingItem = OrderItem::where('order_id', $order->id)
                    ->where('menu_item_id', $menuItemId)
                    ->first();

                if ($existingItem) {
                    $existingItem->quantity += $item['quantity'];
                    $existingItem->save();
                } else {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'menu_item_id' => $menuItemId,
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['price'],
                        'category' => $item['category'],
                        'notes' => ''
                    ]);
                }
            }

            $newTotal = OrderItem::where('order_id', $order->id)
                ->get()
                ->sum(function($item) {
                    return $item->unit_price * $item->quantity;
                });

            $order->total = $newTotal;
            $order->save();

            session()->forget('cart');

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'estimated_time' => $order->estimated_time,
                'message' => 'Articles ajoutés à la commande existante avec succès!',
                'redirect_url' => route('client.order.confirmation', $order->id)
            ]);
        } else {
            $order = Order::create([
                'table_number' => Auth::user()->table_number,
                'total' => $total,
                'status' => 'commandé',
                'payment_status' => 'en_attente',
                'order_type' => $request->order_type,
                'customer_phone' => $request->phone_number,
                'estimated_time' => null,
                'delivery_address' => $request->delivery_address,
                'delivery_notes' => $request->delivery_notes
            ]);

            foreach ($cart as $menuItemId => $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'menu_item_id' => $menuItemId,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'category' => $item['category'],
                    'notes' => ''
                ]);
            }

            session()->forget('cart');

            $redirectUrl = $request->order_type === 'sur_place' 
                ? route('client.order.ussd', $order->id) 
                : route('client.order.confirmation', $order->id);

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'estimated_time' => $order->estimated_time,
                'message' => 'Commande passée avec succès!',
                'redirect_url' => $redirectUrl
            ]);
        }
    }

    /**
     * Afficher la commande USSD pour le paiement
     */
    public function showUssdCommand($orderId)
    {
        if (!Auth::check() || Auth::user()->role !== 'client') {
            return redirect()->route('client.auth');
        }

        $user = Auth::user();
        if ($user->isSuspended()) {
            Auth::logout();
            return redirect()->route('client.auth')->with('error', 'Votre compte a été suspendu. Veuillez contacter l\'administrateur.');
        }

        $order = Order::with(['items.menuItem'])
                     ->where('id', $orderId)
                     ->where('table_number', Auth::user()->table_number)
                     ->firstOrFail();

        $selectedNetwork = request()->get('network', session()->get('selected_network', 'mtn'));
        $ussdCommand = $this->generateUssdCommand($order, $selectedNetwork);

        return view('client.ussd-command', [
            'tableNumber' => Auth::user()->table_number,
            'order' => $order,
            'selectedNetwork' => $selectedNetwork,
            'ussdCommand' => $ussdCommand
        ]);
    }

    /**
     * Générer la commande USSD selon le réseau
     */
    private function generateUssdCommand($order, $network)
    {
        $totalAmount = intval($order->total);
        
        switch ($network) {
            case 'moov':
                return "*855*1*1*0158187101*0158187101*{$totalAmount}*1#";
            case 'mtn':
                return "*880*1*1*0154649143*0154649143*{$totalAmount}#";
            case 'orange':
                return "*855*1*1*0158187101*0158187101*{$totalAmount}*1#";
            default:
                return "*880*1*1*0166110299*0166110299*{$totalAmount}#";
        }
    }

    /**
     * Afficher le formulaire de saisie de l'ID de transaction
     */
    public function showTransactionForm($orderId)
    {
        if (!Auth::check() || Auth::user()->role !== 'client') {
            return redirect()->route('client.auth');
        }

        $user = Auth::user();
        if ($user->isSuspended()) {
            Auth::logout();
            return redirect()->route('client.auth')->with('error', 'Votre compte a été suspendu. Veuillez contacter l\'administrateur.');
        }

        $order = Order::with(['items.menuItem'])
                     ->where('id', $orderId)
                     ->where('table_number', Auth::user()->table_number)
                     ->firstOrFail();

        return view('client.transaction-form', [
            'tableNumber' => Auth::user()->table_number,
            'order' => $order
        ]);
    }

    /**
     * Traiter le formulaire client + matching avec SMS stockés
     */
    public function processTransaction(Request $request, $orderId)
    {
        if (!Auth::check() || Auth::user()->role !== 'client') {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        $user = Auth::user();
        if ($user->isSuspended()) {
            return response()->json([
                'success' => false,
                'message' => 'Votre compte a été suspendu.'
            ], 403);
        }

        $request->validate([
            'transaction_id' => 'required|string|max:50',
            'network' => 'required|in:mtn,moov,orange',
            'phone_number' => 'required|string|max:20'
        ]);

        $order = Order::where('id', $orderId)
                     ->where('table_number', Auth::user()->table_number)
                     ->firstOrFail();

        if ($order->payment_status === 'payé') {
            return response()->json([
                'success' => false,
                'message' => 'Cette commande a déjà été payée.'
            ], 400);
        }

        try {
            // SYNCHRONISER AUTOMATIQUEMENT TOUS LES FICHIERS SMS
            $syncResult = $this->syncAllSMSFiles();
            Log::info("Synchronisation automatique SMS: {$syncResult['imported']} nouveaux SMS importés");

            // Chercher dans les SMS stockés
            $smsTransaction = $this->findMatchingSMS($request, $order);

            if (!$smsTransaction) {
                Log::warning("Aucun SMS correspondant trouvé", [
                    'transaction_id' => $request->transaction_id,
                    'network' => $request->network,
                    'order_id' => $orderId,
                    'order_total' => $order->total,
                    'phone_number' => $request->phone_number
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun SMS de confirmation trouvé. Vérifiez l\'ID de transaction ou attendez que l\'admin exporte les SMS.'
                ], 404);
            }

            // Analyser le SMS pour extraire les infos
            $transactionData = $this->parsePaymentSMS($smsTransaction->message, $smsTransaction->sender_number);
            
            if (!$transactionData) {
                Log::warning("SMS non analysable", [
                    'sms_id' => $smsTransaction->id,
                    'message' => substr($smsTransaction->message, 0, 100)
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Le SMS reçu n\'est pas un SMS de confirmation de paiement valide.'
                ], 400);
            }

            // Vérifier la cohérence des données
            $validation = $this->validateTransactionData($transactionData, $request, $order);
            
            if (!$validation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $validation['message']
                ], 400);
            }

            // Vérifier que la transaction n'est pas déjà utilisée
            $existingPayment = Payment::where('transaction_id', $request->transaction_id)
                ->where('network', $request->network)
                ->where('status', 'verified')
                ->first();

            if ($existingPayment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette transaction a déjà été utilisée pour une autre commande.'
                ], 400);
            }

            // Finaliser la transaction
            $this->finalizeTransaction($smsTransaction, $transactionData, $order, $request);

            Log::info("✅ Paiement validé avec matching SMS", [
                'order_id' => $order->id,
                'sms_id' => $smsTransaction->id,
                'transaction_id' => $request->transaction_id,
                'amount' => $order->total
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Paiement vérifié avec succès! Votre commande est en cours de préparation.',
                'redirect_url' => route('client.order.confirmation', $order->id)
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur processTransaction:', [
                'error' => $e->getMessage(), 
                'order_id' => $orderId,
                'transaction_id' => $request->transaction_id ?? 'N/A'
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * SYNCHRONISER TOUS LES FICHIERS SMS (CORRIGÉ)
     */
    private function syncAllSMSFiles()
    {
        $smsDirectory = storage_path('app/mobiletrans_sms');
        $totalImported = 0;
        
        // Créer le dossier s'il n'existe pas
        if (!file_exists($smsDirectory)) {
            mkdir($smsDirectory, 0755, true);
            Log::info("Dossier MobileTrans créé: {$smsDirectory}");
            return ['imported' => 0, 'message' => 'Dossier créé'];
        }

        // Vérifier TOUS les fichiers (même ceux déjà traités)
        $files = glob($smsDirectory . '/*.{csv,html,txt}', GLOB_BRACE);
        
        foreach ($files as $file) {
            $filename = basename($file);
            Log::info("Traitement du fichier: {$filename}");
            
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            $imported = 0;
            
            switch ($extension) {
                case 'csv':
                    $imported = $this->processCSVFile($file);
                    break;
                case 'html':
                    $imported = $this->processHTMLFile($file);
                    break;
                case 'txt':
                    $imported = $this->processTXTFile($file);
                    break;
            }
            
            $totalImported += $imported;
            Log::info("Fichier {$filename} traité: {$imported} SMS importés");
        }

        return ['imported' => $totalImported, 'message' => "{$totalImported} SMS importés"];
    }

    /**
     * Chercher un SMS qui correspond aux critères (CORRIGÉ)
     */
    private function findMatchingSMS($request, $order)
    {
        Log::info("🔍 Recherche SMS correspondant:", [
            'transaction_id' => $request->transaction_id,
            'network' => $request->network,
            'order_amount' => $order->total,
            'phone' => $request->phone_number
        ]);

        // 1. Chercher par ID de transaction exact dans le message
        $smsByTransactionId = SMSTransaction::where('message', 'LIKE', '%' . $request->transaction_id . '%')
            ->whereIn('status', ['received', 'pending'])
            ->where('sms_received_at', '>=', now()->subHours(24))
            ->first();

        if ($smsByTransactionId) {
            Log::info("✅ SMS trouvé par ID transaction: {$request->transaction_id}");
            return $smsByTransactionId;
        }

        // 2. Chercher dans la colonne transaction_id de la base
        $smsByTransactionColumn = SMSTransaction::where('transaction_id', $request->transaction_id)
            ->whereIn('status', ['received', 'pending'])
            ->where('sms_received_at', '>=', now()->subHours(24))
            ->first();

        if ($smsByTransactionColumn) {
            Log::info("✅ SMS trouvé par colonne transaction_id: {$request->transaction_id}");
            return $smsByTransactionColumn;
        }

        // 3. Chercher par numéro de téléphone exact
        $cleanedPhone = $this->cleanPhoneNumber($request->phone_number);
        
        $smsByPhone = SMSTransaction::where('sender_number', 'LIKE', '%' . $cleanedPhone . '%')
            ->whereIn('status', ['received', 'pending'])
            ->where('sms_received_at', '>=', now()->subHours(24))
            ->first();

        if ($smsByPhone) {
            Log::info("✅ SMS trouvé par numéro: {$cleanedPhone}");
            return $smsByPhone;
        }

        // 4. Chercher par montant approximatif
        $smsByAmount = SMSTransaction::where('amount', '>=', $order->total - 1)
            ->where('amount', '<=', $order->total + 1)
            ->whereIn('status', ['received', 'pending'])
            ->where('sms_received_at', '>=', now()->subHours(24))
            ->first();

        if ($smsByAmount) {
            Log::info("✅ SMS trouvé par montant: {$order->total}");
            return $smsByAmount;
        }

        Log::warning("❌ Aucun SMS correspondant trouvé pour la recherche", [
            'transaction_id' => $request->transaction_id,
            'phone' => $cleanedPhone,
            'amount' => $order->total
        ]);

        return null;
    }

    /**
     * Valider les données de transaction
     */
    private function validateTransactionData($transactionData, $request, $order)
    {
        // Vérifier l'ID de transaction
        if ($transactionData['transaction_id'] !== $request->transaction_id) {
            return [
                'valid' => false,
                'message' => 'L\'ID de transaction ne correspond pas au SMS reçu.'
            ];
        }

        // Vérifier le réseau
        if ($transactionData['network'] !== $request->network) {
            return [
                'valid' => false,
                'message' => 'Le réseau ne correspond pas au SMS reçu.'
            ];
        }

        // Vérifier le montant
        if (abs($transactionData['amount'] - $order->total) > 1) {
            return [
                'valid' => false,
                'message' => 'Le montant du SMS (' . number_format($transactionData['amount'], 0, ',', ' ') . ' FCFA) ne correspond pas à la commande (' . number_format($order->total, 0, ',', ' ') . ' FCFA).'
            ];
        }

        return ['valid' => true, 'message' => 'OK'];
    }

    /**
     * Finaliser la transaction
     */
    private function finalizeTransaction($smsTransaction, $transactionData, $order, $request)
    {
        // Mettre à jour la transaction SMS avec toutes les infos extraites
        $smsTransaction->update([
            'transaction_id' => $transactionData['transaction_id'],
            'amount' => $transactionData['amount'],
            'network' => $transactionData['network'],
            'receiver_number' => $transactionData['receiver_number'] ?? 'N/A',
            'status' => 'used',
            'order_id' => $order->id,
            'verified_at' => now()
        ]);

        // Créer le paiement
        Payment::create([
            'order_id' => $order->id,
            'amount' => $order->total,
            'payment_method' => 'mobile_money',
            'transaction_id' => $request->transaction_id,
            'network' => $request->network,
            'phone_number' => $request->phone_number,
            'status' => 'verified',
            'verified_at' => now()
        ]);

        // Mettre à jour la commande
        $order->update(['payment_status' => 'payé']);
    }

    /**
     * Nettoyer le numéro de téléphone pour comparaison
     */
    private function cleanPhoneNumber($phone)
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        
        // Gérer les numéros avec indicatif 225
        if (strlen($cleaned) === 12 && substr($cleaned, 0, 3) === '225') {
            $cleaned = '0' . substr($cleaned, 3);
        }
        
        // Gérer les numéros avec indicatif +225
        if (strlen($cleaned) === 13 && substr($cleaned, 0, 4) === '2250') {
            $cleaned = '0' . substr($cleaned, 4);
        }
        
        // S'assurer d'avoir un format 10 chiffres
        if (strlen($cleaned) === 9) {
            $cleaned = '0' . $cleaned;
        }
        
        return $cleaned;
    }

    /**
     * Traiter les fichiers CSV (CORRIGÉ)
     */
    private function processCSVFile($filePath)
    {
        $imported = 0;
        Log::info("📂 Traitement du fichier CSV: " . basename($filePath));

        if (($handle = fopen($filePath, 'r')) !== FALSE) {
            // Lire l'en-tête BOM UTF-8 si présent
            $bom = fread($handle, 3);
            if ($bom != "\xEF\xBB\xBF") {
                rewind($handle);
            }

            // Lire l'en-tête
            $header = fgetcsv($handle, 1000, ',');
            Log::info("En-tête CSV détecté:", $header);
            
            while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                if (count($header) === count($data)) {
                    $smsData = array_combine($header, $data);
                    
                    // Adapter selon la structure de votre fichier CSV
                    $message = $smsData['Content'] ?? $smsData['Message'] ?? $smsData['message'] ?? $smsData['Body'] ?? '';
                    $sender = $smsData['Number'] ?? $smsData['Phone'] ?? $smsData['Address'] ?? $smsData['Sender'] ?? 'moovmoney';
                    $date = $smsData['Time'] ?? $smsData['Date'] ?? $smsData['Received'] ?? now();
                    
                    Log::info("SMS lu - Expéditeur: {$sender}, Message: " . substr($message, 0, 50));
                    
                    if (!empty($message) && $this->isPaymentSMS($message)) {
                        // Vérifier si le SMS existe déjà
                        $exists = SMSTransaction::where('message', $message)
                            ->where('sender_number', $sender)
                            ->exists();

                        if (!$exists) {
                            // Analyser le SMS pour extraire les informations
                            $transactionData = $this->parsePaymentSMS($message, $sender);
                            
                            if ($transactionData) {
                                // Créer le SMS avec toutes les valeurs requises
                                $smsRecord = [
                                    'sender_number' => $sender,
                                    'message' => $message,
                                    'sms_received_at' => $this->parseDate($date),
                                    'status' => 'received',
                                    'transaction_id' => $transactionData['transaction_id'] ?? 'N/A',
                                    'network' => $transactionData['network'] ?? 'unknown',
                                    'receiver_number' => $transactionData['receiver_number'] ?? 'N/A'
                                ];
                                
                                // Ajouter le montant seulement s'il est disponible
                                if (isset($transactionData['amount'])) {
                                    $smsRecord['amount'] = $transactionData['amount'];
                                }
                                
                                SMSTransaction::create($smsRecord);
                                $imported++;
                                Log::info("✅ Nouveau SMS importé: {$sender} - " . substr($message, 0, 50));
                            } else {
                                Log::warning("❌ SMS non analysable: " . substr($message, 0, 50));
                            }
                        } else {
                            Log::info("⏭️ SMS déjà existant, ignoré");
                        }
                    }
                }
            }
            fclose($handle);
        } else {
            Log::error("❌ Impossible d'ouvrir le fichier CSV: " . $filePath);
        }

        Log::info("📊 Fichier CSV traité: {$imported} SMS importés");
        return $imported;
    }

    /**
     * Traiter les fichiers HTML
     */
    private function processHTMLFile($filePath)
    {
        $imported = 0;
        $htmlContent = file_get_contents($filePath);
        
        // Pattern générique pour les SMS dans HTML
        preg_match_all('/<div[^>]*class="[^"]*message[^"]*"[^>]*>(.*?)<\/div>/is', $htmlContent, $messageMatches);
        preg_match_all('/<div[^>]*class="[^"]*number[^"]*"[^>]*>(.*?)<\/div>/is', $htmlContent, $numberMatches);
        preg_match_all('/<div[^>]*class="[^"]*date[^"]*"[^>]*>(.*?)<\/div>/is', $htmlContent, $dateMatches);
        
        // Alternative: chercher les numéros de téléphone dans le texte
        if (empty($numberMatches[1])) {
            preg_match_all('/\+?[\d\s\-\(\)]{8,}/', $htmlContent, $numberMatches);
        }
        
        for ($i = 0; $i < count($messageMatches[1]); $i++) {
            $message = trim(strip_tags($messageMatches[1][$i] ?? ''));
            $sender = trim($numberMatches[1][$i] ?? 'Inconnu');
            $date = trim($dateMatches[1][$i] ?? now());
            
            if (!empty($message) && $this->isPaymentSMS($message)) {
                $exists = SMSTransaction::where('message', $message)
                    ->where('sender_number', $sender)
                    ->exists();

                if (!$exists) {
                    // Analyser le SMS pour extraire les informations
                    $transactionData = $this->parsePaymentSMS($message, $sender);
                    
                    // Créer le SMS avec toutes les valeurs requises
                    $smsData = [
                        'sender_number' => $sender,
                        'message' => $message,
                        'sms_received_at' => $this->parseDate($date),
                        'status' => 'received',
                        'transaction_id' => $transactionData['transaction_id'] ?? 'N/A',
                        'network' => $transactionData['network'] ?? 'unknown',
                        'receiver_number' => $transactionData['receiver_number'] ?? 'N/A'
                    ];
                    
                    // Ajouter le montant seulement s'il est disponible
                    if (isset($transactionData['amount'])) {
                        $smsData['amount'] = $transactionData['amount'];
                    }
                    
                    SMSTransaction::create($smsData);
                    $imported++;
                    Log::info("Nouveau SMS HTML importé: {$sender} - " . substr($message, 0, 50));
                }
            }
        }

        return $imported;
    }

    /**
     * Traiter les fichiers TXT
     */
    private function processTXTFile($filePath)
    {
        $imported = 0;
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Pattern pour fichiers TXT avec format: [Date] Sender: Message
            if (preg_match('/\[(.*?)\]\s*([^:]+):\s*(.*)/', $line, $matches)) {
                $date = $matches[1];
                $sender = trim($matches[2]);
                $message = trim($matches[3]);
                
                if ($this->isPaymentSMS($message)) {
                    $exists = SMSTransaction::where('message', $message)
                        ->where('sender_number', $sender)
                        ->exists();

                    if (!$exists) {
                        // Analyser le SMS pour extraire les informations
                        $transactionData = $this->parsePaymentSMS($message, $sender);
                        
                        // Créer le SMS avec toutes les valeurs requises
                        $smsData = [
                            'sender_number' => $sender,
                            'message' => $message,
                            'sms_received_at' => $this->parseDate($date),
                            'status' => 'received',
                            'transaction_id' => $transactionData['transaction_id'] ?? 'N/A',
                            'network' => $transactionData['network'] ?? 'unknown',
                            'receiver_number' => $transactionData['receiver_number'] ?? 'N/A'
                        ];
                        
                        // Ajouter le montant seulement s'il est disponible
                        if (isset($transactionData['amount'])) {
                            $smsData['amount'] = $transactionData['amount'];
                        }
                        
                        SMSTransaction::create($smsData);
                        $imported++;
                        Log::info("Nouveau SMS TXT importé: {$sender} - " . substr($message, 0, 50));
                    }
                }
            }
            // Pattern alternatif pour fichiers simples
            elseif (preg_match('/(\+?[\d\s\-\(\)]{8,}):\s*(.*)/', $line, $matches)) {
                $sender = trim($matches[1]);
                $message = trim($matches[2]);
                
                if ($this->isPaymentSMS($message)) {
                    $exists = SMSTransaction::where('message', $message)
                        ->where('sender_number', $sender)
                        ->exists();

                    if (!$exists) {
                        // Analyser le SMS pour extraire les informations
                        $transactionData = $this->parsePaymentSMS($message, $sender);
                        
                        // Créer le SMS avec toutes les valeurs requises
                        $smsData = [
                            'sender_number' => $sender,
                            'message' => $message,
                            'sms_received_at' => now(),
                            'status' => 'received',
                            'transaction_id' => $transactionData['transaction_id'] ?? 'N/A',
                            'network' => $transactionData['network'] ?? 'unknown',
                            'receiver_number' => $transactionData['receiver_number'] ?? 'N/A'
                        ];
                        
                        // Ajouter le montant seulement s'il est disponible
                        if (isset($transactionData['amount'])) {
                            $smsData['amount'] = $transactionData['amount'];
                        }
                        
                        SMSTransaction::create($smsData);
                        $imported++;
                        Log::info("Nouveau SMS TXT simple importé: {$sender} - " . substr($message, 0, 50));
                    }
                }
            }
        }

        return $imported;
    }

    /**
     * Parser les dates selon différents formats
     */
    private function parseDate($dateString)
    {
        try {
            // Gérer le format français dd/mm/yyyy
            if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $dateString)) {
                return \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $dateString);
            }
            return \Carbon\Carbon::parse($dateString);
        } catch (\Exception $e) {
            Log::warning("Erreur parsing date: {$dateString}, utilisation de now()");
            return now();
        }
    }

    /**
     * Vérifier si un SMS est un SMS de paiement
     */
    private function isPaymentSMS($message)
    {
        $keywords = ['FCFA', 'XOF', 'mtn', 'moov', 'orange', 'transaction', 'ref', 'paiement', 'transfert', 'mobile money', 'money', 'montant', 'reçu', 'solde'];
        $message = strtolower($message);
        
        foreach ($keywords as $keyword) {
            if (strpos($message, strtolower($keyword)) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Analyser le SMS pour détecter un paiement (AMÉLIORÉ)
     */
    private function parsePaymentSMS($message, $senderNumber)
    {
        $message = trim($message);
        $lowerMessage = strtolower($message);
        
        $network = $this->detectNetwork($lowerMessage, $senderNumber);
        
        if (!$network) {
            Log::warning("Réseau non détecté pour le SMS: " . substr($message, 0, 50));
            return null;
        }

        Log::info("🔍 Analyse SMS {$network}: " . substr($message, 0, 50));

        switch ($network) {
            case 'mtn':
                return $this->parseMTNSMS($message);
            case 'moov':
                return $this->parseMoovSMS($message);
            case 'orange':
                return $this->parseOrangeSMS($message);
            default:
                return null;
        }
    }

    /**
     * Détecter le réseau à partir du message ou du numéro
     */
    private function detectNetwork($message, $senderNumber)
    {
        if (strpos($message, 'mtn') !== false || preg_match('/\b(mtn|mobile money)\b/i', $message)) {
            return 'mtn';
        }
        
        if (strpos($message, 'moov') !== false || preg_match('/\b(moov|flooz)\b/i', $message)) {
            return 'moov';
        }
        
        if (strpos($message, 'orange') !== false || preg_match('/\b(orange money)\b/i', $message)) {
            return 'orange';
        }
        
        // Détection par numéro d'expéditeur
        $cleanSender = preg_replace('/[^0-9]/', '', $senderNumber);
        
        if (strpos($cleanSender, 'moov') !== false || strpos($senderNumber, 'moov') !== false) {
            return 'moov';
        }
        
        return 'moov'; // Par défaut pour vos tests
    }

    /**
     * Parser les SMS MTN Money
     */
    private function parseMTNSMS($message)
    {
        if (preg_match('/Vous avez reçu (\d+(?:[.,]\d+)?)\s*FCFA de (\d+).*?Ref\.? :?\s*([A-Z0-9]+)/i', $message, $matches)) {
            $amount = floatval(str_replace([' ', ',', ';'], ['', '.', '.'], $matches[1]));
            return [
                'transaction_id' => trim($matches[3]),
                'amount' => $amount,
                'network' => 'mtn',
                'receiver_number' => $matches[2]
            ];
        }
        
        if (preg_match('/Transaction ID:?\s*([A-Z0-9]+).*?Montant:?\s*(\d+(?:[.,]\d+)?)\s*FCFA/i', $message, $matches)) {
            $amount = floatval(str_replace([' ', ',', ';'], ['', '.', '.'], $matches[2]));
            return [
                'transaction_id' => trim($matches[1]),
                'amount' => $amount,
                'network' => 'mtn'
            ];
        }
        
        // Pattern simplifié pour MTN
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*FCFA.*?([A-Z0-9]{6,12})/i', $message, $matches)) {
            $amount = floatval(str_replace([' ', ',', ';'], ['', '.', '.'], $matches[1]));
            return [
                'transaction_id' => trim($matches[2]),
                'amount' => $amount,
                'network' => 'mtn'
            ];
        }
        
        return null;
    }

    /**
     * Parser les SMS Moov Money (AMÉLIORÉ)
     */
    private function parseMoovSMS($message)
    {
        Log::info("🔍 Analyse Moov SMS: " . substr($message, 0, 100));

        // Pattern pour réception d'argent avec Ref
        if (preg_match('/Vous avez recu (\d+(?:[.,;\s]\d+)*)\s*FCFA.*?Ref\s*:?\s*([A-Z0-9]+)/i', $message, $matches)) {
            $amount = floatval(str_replace([' ', ',', ';'], ['', '.', '.'], $matches[1]));
            Log::info("✅ Moov pattern 1 - Montant: {$amount}, Ref: " . $matches[2]);
            return [
                'transaction_id' => trim($matches[2]),
                'amount' => $amount,
                'network' => 'moov'
            ];
        }

        // Pattern pour envoi d'argent avec Ref
        if (preg_match('/Vous avez envoye (\d+(?:[.,;\s]\d+)*)\s*FCFA.*?Ref\s*:?\s*([A-Z0-9]+)/i', $message, $matches)) {
            $amount = floatval(str_replace([' ', ',', ';'], ['', '.', '.'], $matches[1]));
            Log::info("✅ Moov pattern 2 - Montant: {$amount}, Ref: " . $matches[2]);
            return [
                'transaction_id' => trim($matches[2]),
                'amount' => $amount,
                'network' => 'moov'
            ];
        }

        // Pattern pour paiement avec Txn ID
        if (preg_match('/Txn ID:\s*([A-Z0-9]+).*?paye\s*(\d+(?:[.,;\s]\d+)*)\s*FCFA/i', $message, $matches)) {
            $amount = floatval(str_replace([' ', ',', ';'], ['', '.', '.'], $matches[2]));
            Log::info("✅ Moov pattern 3 - Montant: {$amount}, Txn ID: " . $matches[1]);
            return [
                'transaction_id' => trim($matches[1]),
                'amount' => $amount,
                'network' => 'moov'
            ];
        }

        // Pattern générique pour Moov avec Ref à la fin
        if (preg_match('/(\d+(?:[.,;\s]\d+)*)\s*FCFA.*?Ref\s*:?\s*([A-Z0-9]{10,20})/i', $message, $matches)) {
            $amount = floatval(str_replace([' ', ',', ';'], ['', '.', '.'], $matches[1]));
            Log::info("✅ Moov pattern 4 - Montant: {$amount}, Ref: " . $matches[2]);
            return [
                'transaction_id' => trim($matches[2]),
                'amount' => $amount,
                'network' => 'moov'
            ];
        }

        Log::warning("❌ Aucun pattern Moov reconnu");
        return null;
    }

    /**
     * Parser les SMS Orange Money
     */
    private function parseOrangeSMS($message)
    {
        if (preg_match('/Transaction:?\s*([A-Z0-9]+).*?Montant:?\s*(\d+(?:[.,]\d+)?)\s*FCFA/i', $message, $matches)) {
            $amount = floatval(str_replace([' ', ',', ';'], ['', '.', '.'], $matches[2]));
            return [
                'transaction_id' => trim($matches[1]),
                'amount' => $amount,
                'network' => 'orange'
            ];
        }
        
        return null;
    }

    /**
     * Ajouter des articles à une commande existante
     */
    public function addToExistingOrder(Request $request, $orderId)
    {
        $user = Auth::user();
        if ($user->isSuspended()) {
            return response()->json([
                'success' => false, 
                'message' => 'Votre compte a été suspendu. Vous ne pouvez pas ajouter d\'articles.'
            ], 403);
        }

        $request->validate([
            'menu_item_id' => 'required|exists:menu_items,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $order = Order::where('id', $orderId)
            ->where('table_number', Auth::user()->table_number)
            ->firstOrFail();

        $existingItem = OrderItem::where('order_id', $order->id)
            ->where('menu_item_id', $request->menu_item_id)
            ->first();

        $menuItem = MenuItem::find($request->menu_item_id);

        if ($existingItem) {
            $existingItem->quantity += $request->quantity;
            $existingItem->save();
        } else {
            OrderItem::create([
                'order_id' => $order->id,
                'menu_item_id' => $request->menu_item_id,
                'quantity' => $request->quantity,
                'unit_price' => $menuItem->price,
                'category' => $menuItem->category,
                'notes' => ''
            ]);
        }

        $total = OrderItem::where('order_id', $order->id)
            ->get()
            ->sum(function($item) {
                return $item->unit_price * $item->quantity;
            });

        $order->total = $total;
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Article ajouté à la commande existante!',
            'order_total' => $total
        ]);
    }

    /**
     * Afficher la confirmation de commande
     */
    public function orderConfirmation($orderId)
    {
        if (!Auth::check() || Auth::user()->role !== 'client') {
            return redirect()->route('client.auth');
        }

        $user = Auth::user();
        if ($user->isSuspended()) {
            Auth::logout();
            return redirect()->route('client.auth')->with('error', 'Votre compte a été suspendu. Veuillez contacter l\'administrateur.');
        }

        $order = Order::with(['items.menuItem'])
                     ->where('id', $orderId)
                     ->where('table_number', Auth::user()->table_number)
                     ->firstOrFail();

        return view('client.order-confirmation', [
            'tableNumber' => Auth::user()->table_number,
            'order' => $order
        ]);
    }

    public function getOrderStatus($orderId)
    {
        $order = Order::with('items.menuItem')->find($orderId);
        
        if (!$order) {
            return response()->json(['error' => 'Commande non trouvée'], 404);
        }

        return response()->json([
            'status' => $order->status,
            'estimated_time' => $order->estimated_time,
            'marked_ready_at' => $order->marked_ready_at,
            'payment_status' => $order->payment_status
        ]);
    }

    /**
     * Demander la livraison pour une commande existante
     */
    public function requestDelivery(Request $request, $orderId)
    {
        $user = Auth::user();
        if ($user->isSuspended()) {
            return response()->json([
                'success' => false, 
                'message' => 'Votre compte a été suspendu. Vous ne pouvez pas demander la livraison.'
            ], 403);
        }

        $request->validate([
            'delivery_address' => 'required|string|max:255',
            'delivery_notes' => 'nullable|string|max:500'
        ]);

        $order = Order::where('id', $orderId)
            ->where('table_number', Auth::user()->table_number)
            ->firstOrFail();

        if ($order->status === 'terminé' || $order->status === 'prêt') {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de demander la livraison pour une commande déjà prête ou terminée.'
            ]);
        }

        $order->update([
            'order_type' => 'livraison',
            'delivery_address' => $request->delivery_address,
            'delivery_notes' => $request->delivery_notes,
            'status' => 'en_cours'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demande de livraison envoyée avec succès! Notre équipe vous apportera votre commande.',
            'order_type' => $order->order_type
        ]);
    }

    /**
     * Afficher l'historique des commandes
     */
    public function orderHistory()
    {
        if (!Auth::check() || Auth::user()->role !== 'client') {
            return redirect()->route('client.auth');
        }

        $user = Auth::user();
        if ($user->isSuspended()) {
            Auth::logout();
            return redirect()->route('client.auth')->with('error', 'Votre compte a été suspendu. Veuillez contacter l\'administrateur.');
        }

        $user = Auth::user();
        
        $orders = Order::where('table_number', $user->table_number)
            ->with('items.menuItem')
            ->orderBy('created_at', 'desc')
            ->get();

        $debug = [
            'count_all' => $orders->count(),
            'payment_statuses' => $orders->pluck('payment_status')->unique(),
            'statuses' => $orders->pluck('status')->unique()
        ];

        return view('client.order-history', [
            'orders' => $orders,
            'tableNumber' => $user->table_number,
            'debug' => $debug
        ]);
    }

    /**
     * API pour forcer la synchronisation manuelle (pour debug)
     */
    public function forceSyncSMS(Request $request)
    {
        try {
            $result = $this->syncAllSMSFiles();
            
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'imported' => $result['imported']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
}
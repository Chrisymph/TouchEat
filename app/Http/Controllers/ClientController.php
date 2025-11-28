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
            // FORCER la synchronisation des fichiers SMS
            Log::info("🔄 FORCE Synchronisation SMS pour la transaction: " . $request->transaction_id);
            $syncResult = $this->syncAllSMSFiles();
            Log::info("Synchronisation SMS résultat: {$syncResult['imported']} nouveaux SMS importés");

            // Attendre un peu pour être sûr que les données sont sauvegardées
            sleep(2);

            // DEBUG: Afficher tous les SMS dans la base
            $allSMS = SMSTransaction::where('status', 'received')
                ->where('sms_received_at', '>=', now()->subDays(2))
                ->get();
            
            Log::info("📋 SMS dans la base de données:", [
                'total' => $allSMS->count(),
                'sms_list' => $allSMS->map(function($sms) {
                    return [
                        'id' => $sms->id,
                        'transaction_id' => $sms->transaction_id,
                        'sender' => $sms->sender_number,
                        'amount' => $sms->amount,
                        'message_preview' => substr($sms->message, 0, 50)
                    ];
                })->toArray()
            ]);

            // Chercher dans les SMS stockés avec une recherche plus large
            $smsTransaction = $this->findMatchingSMS($request, $order);

            if (!$smsTransaction) {
                Log::warning("❌ Aucun SMS correspondant trouvé après recherche étendue", [
                    'transaction_id' => $request->transaction_id,
                    'network' => $request->network,
                    'order_id' => $orderId,
                    'order_total' => $order->total,
                    'phone_number' => $request->phone_number,
                    'search_time' => now()->toDateTimeString()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun SMS de confirmation trouvé pour cette transaction. Vérifiez que : 1) L\'ID de transaction est exact 2) Le SMS est bien arrivé 3) Le numéro de téléphone est correct'
                ], 404);
            }

            Log::info("✅ SMS trouvé:", [
                'sms_id' => $smsTransaction->id,
                'transaction_id' => $smsTransaction->transaction_id,
                'sender' => $smsTransaction->sender_number,
                'amount' => $smsTransaction->amount,
                'message_preview' => substr($smsTransaction->message, 0, 100)
            ]);

            // Analyser le SMS pour extraire les infos
            $transactionData = $this->parsePaymentSMS($smsTransaction->message, $smsTransaction->sender_number);
            
            if (!$transactionData) {
                Log::warning("❌ SMS non analysable", [
                    'sms_id' => $smsTransaction->id,
                    'message' => $smsTransaction->message
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Le SMS reçu n\'est pas un SMS de confirmation de paiement valide.'
                ], 400);
            }

            // Vérifier la cohérence des données
            $validation = $this->validateTransactionData($transactionData, $request, $order, $smsTransaction);
            
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

            Log::info("🎉 Paiement validé avec succès!", [
                'order_id' => $order->id,
                'sms_id' => $smsTransaction->id,
                'transaction_id' => $request->transaction_id,
                'amount' => $order->total,
                'phone_number' => $request->phone_number
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Paiement vérifié avec succès! Votre commande est en cours de préparation.',
                'redirect_url' => route('client.order.confirmation', $order->id)
            ]);

        } catch (\Exception $e) {
            Log::error('💥 Erreur processTransaction:', [
                'error' => $e->getMessage(), 
                'trace' => $e->getTraceAsString(),
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
     * Chercher un SMS qui correspond aux critères
     */
    private function findMatchingSMS($request, $order)
    {
        $transactionId = trim($request->transaction_id);
        $phoneNumber = trim($request->phone_number);
        $network = $request->network;
        $orderAmount = $order->total;

        Log::info("🔍 RECHERCHE SMS DÉTAILLÉE:", [
            'transaction_id' => $transactionId,
            'phone_number' => $phoneNumber,
            'network' => $network,
            'order_amount' => $orderAmount,
            'search_time' => now()->toDateTimeString()
        ]);

        $cleanedPhone = $this->cleanPhoneNumber($phoneNumber);

        // STRATÉGIE 1: Recherche EXACTE par transaction_id (priorité maximale)
        $exactMatch = SMSTransaction::where('transaction_id', $transactionId)
            ->where('status', 'received')
            ->where('sms_received_at', '>=', now()->subHours(48))
            ->first();

        if ($exactMatch) {
            Log::info("✅ STRATÉGIE 1: SMS trouvé par transaction_id exact", [
                'sms_id' => $exactMatch->id,
                'transaction_id' => $exactMatch->transaction_id
            ]);
            return $exactMatch;
        }

        // STRATÉGIE 2: Recherche dans le message avec transaction_id exact
        $messageMatch = SMSTransaction::where('message', 'LIKE', '%' . $transactionId . '%')
            ->where('status', 'received')
            ->where('sms_received_at', '>=', now()->subHours(48))
            ->first();

        if ($messageMatch) {
            Log::info("✅ STRATÉGIE 2: SMS trouvé par transaction_id dans message", [
                'sms_id' => $messageMatch->id,
                'transaction_id_in_message' => $transactionId
            ]);
            return $messageMatch;
        }

        // STRATÉGIE 3: Recherche par numéro de téléphone + montant exact
        $phoneAmountMatch = SMSTransaction::where(function($query) use ($cleanedPhone) {
                $query->where('sender_number', 'LIKE', '%' . $cleanedPhone . '%')
                      ->orWhere('message', 'LIKE', '%' . $cleanedPhone . '%');
            })
            ->whereBetween('amount', [$orderAmount - 0.5, $orderAmount + 0.5])
            ->where('status', 'received')
            ->where('sms_received_at', '>=', now()->subHours(48))
            ->first();

        if ($phoneAmountMatch) {
            Log::info("✅ STRATÉGIE 3: SMS trouvé par numéro + montant exact", [
                'sms_id' => $phoneAmountMatch->id,
                'phone_match' => $cleanedPhone,
                'amount_match' => $phoneAmountMatch->amount
            ]);
            return $phoneAmountMatch;
        }

        // STRATÉGIE 4: Recherche par montant exact seulement (dernier recours)
        $amountMatch = SMSTransaction::whereBetween('amount', [$orderAmount - 0.5, $orderAmount + 0.5])
            ->where('status', 'received')
            ->where('sms_received_at', '>=', now()->subHours(48))
            ->first();

        if ($amountMatch) {
            Log::info("✅ STRATÉGIE 4: SMS trouvé par montant exact seulement", [
                'sms_id' => $amountMatch->id,
                'amount' => $amountMatch->amount
            ]);
            return $amountMatch;
        }

        Log::warning("❌ AUCUN SMS TROUVÉ après toutes les stratégies", [
            'transaction_id' => $transactionId,
            'phone_cleaned' => $cleanedPhone,
            'order_amount' => $orderAmount,
            'network' => $network
        ]);

        return null;
    }

    /**
     * Valider les données de transaction
     */
    private function validateTransactionData($transactionData, $request, $order, $smsTransaction)
    {
        $transactionId = trim($request->transaction_id);
        $phoneNumber = trim($request->phone_number);
        $orderAmount = $order->total;

        // 1. Vérifier l'ID de transaction
        if ($transactionData['transaction_id'] !== $transactionId) {
            Log::warning("❌ ID transaction ne correspond pas", [
                'expected' => $transactionId,
                'actual' => $transactionData['transaction_id']
            ]);
            return [
                'valid' => false,
                'message' => 'L\'ID de transaction ne correspond pas au SMS reçu.'
            ];
        }

        // 2. Vérifier le réseau
        if ($transactionData['network'] !== $request->network) {
            return [
                'valid' => false,
                'message' => 'Le réseau ne correspond pas au SMS reçu.'
            ];
        }

        // 3. Vérifier le montant (tolérance très faible)
        if (abs($transactionData['amount'] - $orderAmount) > 0.5) {
            return [
                'valid' => false,
                'message' => 'Le montant du SMS (' . number_format($transactionData['amount'], 0, ',', ' ') . ' FCFA) ne correspond pas à la commande (' . number_format($orderAmount, 0, ',', ' ') . ' FCFA).'
            ];
        }

        // 4. VÉRIFICATION CRITIQUE: Vérifier que le numéro de téléphone correspond
        $phoneValidation = $this->verifyPhoneNumberMatch($smsTransaction, $phoneNumber, $transactionData);
        
        if (!$phoneValidation['valid']) {
            Log::warning("❌ Numéro de téléphone ne correspond pas", [
                'provided_phone' => $phoneNumber,
                'sms_sender' => $smsTransaction->sender_number,
                'message_content' => substr($smsTransaction->message, 0, 100)
            ]);
            return [
                'valid' => false,
                'message' => $phoneValidation['message']
            ];
        }

        Log::info("✅ Toutes les validations passées avec succès");
        return ['valid' => true, 'message' => 'OK'];
    }

    /**
     * Vérifier la correspondance du numéro de téléphone
     */
    private function verifyPhoneNumberMatch($smsTransaction, $providedPhone, $transactionData)
    {
        $cleanedProvidedPhone = $this->cleanPhoneNumber($providedPhone);
        
        Log::info("🔍 VÉRIFICATION NUMÉRO:", [
            'provided_phone' => $providedPhone,
            'cleaned_phone' => $cleanedProvidedPhone,
            'sms_sender' => $smsTransaction->sender_number,
            'sms_id' => $smsTransaction->id
        ]);

        // 1. Vérifier dans le numéro d'expéditeur du SMS
        $cleanedSender = $this->cleanPhoneNumber($smsTransaction->sender_number);
        if ($cleanedSender && $this->comparePhoneNumbers($cleanedSender, $cleanedProvidedPhone)) {
            Log::info("✅ Numéro vérifié dans l'expéditeur SMS", [
                'provided' => $cleanedProvidedPhone,
                'sender' => $cleanedSender
            ]);
            return ['valid' => true, 'message' => 'OK'];
        }

        // 2. Vérifier dans le message du SMS (recherche exacte)
        if (strpos($smsTransaction->message, $cleanedProvidedPhone) !== false) {
            Log::info("✅ Numéro vérifié dans le message SMS (exact match)");
            return ['valid' => true, 'message' => 'OK'];
        }

        // 3. Extraire tous les numéros du message et vérifier
        $phonesInMessage = $this->extractPhoneNumbersFromMessage($smsTransaction->message);
        foreach ($phonesInMessage as $phoneInMessage) {
            $cleanedPhoneInMessage = $this->cleanPhoneNumber($phoneInMessage);
            if ($this->comparePhoneNumbers($cleanedPhoneInMessage, $cleanedProvidedPhone)) {
                Log::info("✅ Numéro vérifié dans les numéros extraits du message", [
                    'provided' => $cleanedProvidedPhone,
                    'found_in_message' => $cleanedPhoneInMessage
                ]);
                return ['valid' => true, 'message' => 'OK'];
            }
        }

        // 4. Vérifier dans les données extraites de la transaction
        if (isset($transactionData['sender_phone'])) {
            $cleanedTransactionPhone = $this->cleanPhoneNumber($transactionData['sender_phone']);
            if ($this->comparePhoneNumbers($cleanedTransactionPhone, $cleanedProvidedPhone)) {
                Log::info("✅ Numéro vérifié dans les données transaction");
                return ['valid' => true, 'message' => 'OK'];
            }
        }

        Log::warning("❌ Aucune correspondance de numéro trouvée", [
            'provided_phone' => $cleanedProvidedPhone,
            'sender_phone' => $cleanedSender,
            'phones_in_message' => $phonesInMessage,
            'message_preview' => substr($smsTransaction->message, 0, 100)
        ]);

        return [
            'valid' => false,
            'message' => 'Le numéro de téléphone saisi ne correspond pas au numéro utilisé pour la transaction. Vérifiez que le numéro est exact.'
        ];
    }

    /**
     * Comparer deux numéros de téléphone
     */
    private function comparePhoneNumbers($phone1, $phone2)
    {
        if (!$phone1 || !$phone2) return false;

        // Normaliser les numéros
        $p1 = $this->cleanPhoneNumber($phone1);
        $p2 = $this->cleanPhoneNumber($phone2);

        // Comparaison exacte
        if ($p1 === $p2) return true;

        // Comparaison des 8 derniers chiffres (sans l'indicatif)
        if (strlen($p1) >= 8 && strlen($p2) >= 8) {
            $last8p1 = substr($p1, -8);
            $last8p2 = substr($p2, -8);
            if ($last8p1 === $last8p2) return true;
        }

        return false;
    }

    /**
     * Extraire tous les numéros de téléphone d'un message
     */
    private function extractPhoneNumbersFromMessage($message)
    {
        $phones = [];
        
        // Pattern pour numéros avec 8 à 15 chiffres
        preg_match_all('/\b\d{8,15}\b/', $message, $matches);
        
        foreach ($matches[0] as $phone) {
            $cleaned = $this->cleanPhoneNumber($phone);
            if (strlen($cleaned) >= 8) {
                $phones[] = $cleaned;
            }
        }

        return array_unique($phones);
    }

    /**
     * Nettoyer le numéro de téléphone pour comparaison
     */
    private function cleanPhoneNumber($phone)
    {
        if (empty($phone)) return '';

        // Supprimer tous les caractères non numériques
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        
        // Gérer les numéros avec indicatif 225
        if (strlen($cleaned) === 12 && substr($cleaned, 0, 3) === '225') {
            $cleaned = '0' . substr($cleaned, 3);
        }
        
        // Gérer les numéros avec indicatif +225
        if (strlen($cleaned) === 13 && substr($cleaned, 0, 4) === '2250') {
            $cleaned = '0' . substr($cleaned, 4);
        }
        
        // S'assurer d'avoir un format cohérent (au moins 8 chiffres)
        if (strlen($cleaned) === 9) {
            $cleaned = '0' . $cleaned;
        }

        // Retourner les 10 derniers chiffres si plus long
        if (strlen($cleaned) > 10) {
            $cleaned = substr($cleaned, -10);
        }
        
        return $cleaned;
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
     * SYNCHRONISER TOUS LES FICHIERS SMS
     */
    private function syncAllSMSFiles()
    {
        $smsDirectory = storage_path('app/mobiletrans_sms');
        $totalImported = 0;
        
        Log::info("🔄 DÉBUT SYNCHRONISATION SMS");
        
        // Créer le dossier s'il n'existe pas
        if (!file_exists($smsDirectory)) {
            mkdir($smsDirectory, 0755, true);
            Log::info("📁 Dossier créé: {$smsDirectory}");
            return ['imported' => 0, 'message' => 'Dossier créé'];
        }

        // Vérifier TOUS les fichiers
        $files = glob($smsDirectory . '/*.{csv,html,txt}', GLOB_BRACE);
        
        Log::info("📂 Fichiers trouvés: " . count($files));
        
        foreach ($files as $file) {
            $filename = basename($file);
            Log::info("📄 Traitement du fichier: {$filename}");
            
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
            Log::info("✅ Fichier {$filename} traité: {$imported} SMS importés");
        }

        // Vérifier si le SMS spécifique est maintenant dans la base
        $targetSMS = SMSTransaction::where('transaction_id', '030812360189')->first();
        if ($targetSMS) {
            Log::info("🎯 SUCCÈS: SMS 030812360189 trouvé dans la base! ID: {$targetSMS->id}");
        } else {
            Log::warning("⚠️ ATTENTION: SMS 030812360189 toujours pas trouvé après importation");
            
            // Afficher tous les SMS dans la base pour debug
            $allSMS = SMSTransaction::orderBy('id', 'desc')->limit(10)->get();
            Log::info("📋 Derniers SMS dans la base:", $allSMS->map(function($sms) {
                return [
                    'id' => $sms->id,
                    'transaction_id' => $sms->transaction_id,
                    'amount' => $sms->amount,
                    'message_preview' => substr($sms->message, 0, 50)
                ];
            })->toArray());
        }

        Log::info("📈 SYNCHRONISATION TERMINÉE: {$totalImported} SMS importés au total");
        
        return ['imported' => $totalImported, 'message' => "{$totalImported} SMS importés"];
    }

    /**
     * Traiter les fichiers CSV
     */
    private function processCSVFile($filePath)
    {
        $imported = 0;
        $filename = basename($filePath);
        Log::info("🔄 TRAITEMENT CSV: {$filename}");

        if (($handle = fopen($filePath, 'r')) !== FALSE) {
            // Lire et ignorer le BOM UTF-8 si présent
            $bom = fread($handle, 3);
            if ($bom != "\xEF\xBB\xBF") {
                rewind($handle);
            }

            // Lire l'en-tête
            $header = fgetcsv($handle, 1000, ',');
            if (!$header) {
                fclose($handle);
                Log::error("❌ En-tête CSV vide");
                return 0;
            }

            Log::info("📊 Structure CSV détectée:", $header);

            $lineNumber = 0;
            while (($data = fgetcsv($handle, 3000, ',')) !== FALSE) {
                $lineNumber++;
                
                // Vérifier que nous avons le bon nombre de colonnes
                if (count($header) === count($data)) {
                    $smsData = array_combine($header, $data);
                    
                    // EXTRAIRE LES DONNÉES SELON VOTRE FORMAT EXACT
                    $message = $smsData['Content'] ?? '';
                    $sender = $smsData['Number'] ?? 'moovmoney';
                    $date = $smsData['Time'] ?? now();
                    
                    Log::info("📱 Ligne {$lineNumber} - Expéditeur: {$sender}, Date: {$date}");

                    // Nettoyer le message
                    $cleanMessage = $this->cleanMessage($message);
                    
                    if (!empty($cleanMessage)) {
                        Log::info("📨 Message original: " . substr($message, 0, 100));
                        Log::info("🧹 Message nettoyé: " . substr($cleanMessage, 0, 100));
                        
                        // Vérifier si c'est un SMS de paiement
                        if ($this->isPaymentSMS($cleanMessage)) {
                            Log::info("💰 SMS de paiement détecté ligne {$lineNumber}");
                            
                            // Analyser le SMS pour extraire les informations
                            $transactionData = $this->parsePaymentSMS($cleanMessage, $sender);
                            
                            if ($transactionData) {
                                Log::info("✅ SMS analysé avec succès:", [
                                    'transaction_id' => $transactionData['transaction_id'],
                                    'amount' => $transactionData['amount'],
                                    'network' => $transactionData['network']
                                ]);

                                // Vérifier si ce SMS existe déjà
                                $exists = SMSTransaction::where('transaction_id', $transactionData['transaction_id'])
                                    ->orWhere('message', $cleanMessage)
                                    ->exists();

                                if (!$exists) {
                                    try {
                                        // Créer l'enregistrement SMS
                                        $smsRecord = [
                                            'sender_number' => $sender,
                                            'message' => $cleanMessage,
                                            'sms_received_at' => $this->parseDate($date),
                                            'status' => 'received',
                                            'transaction_id' => $transactionData['transaction_id'],
                                            'network' => $transactionData['network'],
                                            'receiver_number' => $transactionData['receiver_number'] ?? 'N/A',
                                            'amount' => $transactionData['amount']
                                        ];

                                        $sms = SMSTransaction::create($smsRecord);
                                        $imported++;
                                        
                                        Log::info("🎉 SMS IMPORTÉ - ID: {$sms->id}, Ref: {$transactionData['transaction_id']}, Montant: {$transactionData['amount']} FCFA");
                                        
                                        // Vérification spéciale pour le SMS qui nous intéresse
                                        if ($transactionData['transaction_id'] == '030812360189') {
                                            Log::info("🎯 SMS CRITIQUE TROUVÉ!: Transaction 030812360189 importée avec succès!");
                                        }
                                        
                                    } catch (\Exception $e) {
                                        Log::error("❌ Erreur insertion SMS ligne {$lineNumber}: " . $e->getMessage());
                                    }
                                } else {
                                    Log::info("⏭️ SMS déjà existant ligne {$lineNumber} - Ref: {$transactionData['transaction_id']}");
                                }
                            } else {
                                Log::warning("⚠️ SMS non analysable ligne {$lineNumber}");
                                // Sauvegarder quand même le SMS même s'il n'est pas analysable
                                $this->saveUnparsedSMS($cleanMessage, $sender, $date);
                            }
                        } else {
                            Log::info("📭 SMS non-paiement ignoré ligne {$lineNumber}");
                        }
                    }
                } else {
                    Log::warning("📏 Ligne {$lineNumber}: Incohérence colonnes", [
                        'header' => count($header),
                        'data' => count($data)
                    ]);
                }
            }
            fclose($handle);
        } else {
            Log::error("❌ Impossible d'ouvrir le fichier: {$filePath}");
        }

        Log::info("📈 IMPORTATION TERMINÉE: {$imported} SMS importés depuis {$filename}");
        return $imported;
    }

    /**
     * Sauvegarder les SMS non analysables
     */
    private function saveUnparsedSMS($message, $sender, $date)
    {
        try {
            // Vérifier si le SMS existe déjà
            $exists = SMSTransaction::where('message', $message)
                ->where('sender_number', $sender)
                ->exists();

            if (!$exists) {
                SMSTransaction::create([
                    'sender_number' => $sender,
                    'message' => $message,
                    'sms_received_at' => $this->parseDate($date),
                    'status' => 'received',
                    'transaction_id' => 'N/A',
                    'network' => 'unknown',
                    'receiver_number' => 'N/A',
                    'amount' => null
                ]);
                Log::info("💾 SMS non-analysé sauvegardé");
            }
        } catch (\Exception $e) {
            Log::error("❌ Erreur sauvegarde SMS non-analysé: " . $e->getMessage());
        }
    }

    /**
     * Nettoyer le message
     */
    private function cleanMessage($message)
    {
        if (empty($message)) return '';

        // Supprimer les caractères de contrôle
        $message = preg_replace('/[\x00-\x1F\x7F]/u', '', $message);
        
        // Remplacer les caractères mal encodés spécifiques à votre fichier
        $replacements = [
            'ďťż' => '', 'â' => "'", 'â' => "'", 'Â' => '',
            'â' => "'", 'â' => '"', 'â' => '"', 'â¢' => '-',
            'â¦' => '...', 'â' => '-', 'â' => '—',
            'Ã©' => 'é', 'Ã¨' => 'è', 'Ã¢' => 'â', 'Ãª' => 'ê',
            'Ã®' => 'î', 'Ã´' => 'ô', 'Ã»' => 'û', 'Ã§' => 'ç',
            'Ã¯' => 'ï', 'Ã«' => 'ë', 'Ã¹' => 'ù', 'Ã¤' => 'ä',
            'Ã¶' => 'ö', 'Ã¼' => 'ü', 'Â°' => '°'
        ];
        
        $message = str_replace(array_keys($replacements), array_values($replacements), $message);
        
        // Supprimer les espaces multiples
        $message = preg_replace('/\s+/', ' ', $message);
        
        return trim($message);
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
        $keywords = [
            'FCFA', 'XOF', 'mtn', 'moov', 'orange', 
            'transaction', 'ref', 'paiement', 'transfert', 
            'mobile money', 'money', 'montant', 'reçu',
            'solde', 'envoye', 'recu', 'agent'
        ];
        
        $lowerMessage = strtolower($message);
        
        foreach ($keywords as $keyword) {
            if (strpos($lowerMessage, strtolower($keyword)) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Analyser le SMS pour détecter un paiement
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
                'receiver_number' => $matches[2],
                'sender_phone' => $matches[2]
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
     * Parser les SMS Moov Money
     */
    private function parseMoovSMS($message)
    {
        Log::info("🔍 Analyse Moov SMS: " . substr($message, 0, 80));

        // PATTERN 1: Réception d'argent avec Agent (VOTRE FORMAT)
        // "Vous avez recu X FCFA de l Agent Y le Date. Ref: Z"
        if (preg_match('/Vous avez recu (\d+(?:[.,;\s]\d+)*)\s*FCFA de l\s?Agent\s+([\d\s]+)\s+le\s+[\d\/]+\s+[\d:]+\.\s*Ref\s*:\s*([A-Z0-9]+)/i', $message, $matches)) {
            $amount = $this->parseAmount($matches[1]);
            $agentNumber = preg_replace('/[^0-9]/', '', $matches[2]);
            
            Log::info("✅ PATTERN 1 - Réception Agent", [
                'montant' => $amount,
                'agent' => $agentNumber,
                'ref' => $matches[3]
            ]);
            
            return [
                'transaction_id' => trim($matches[3]),
                'amount' => $amount,
                'network' => 'moov',
                'sender_phone' => $agentNumber,
                'receiver_number' => $agentNumber
            ];
        }

        // PATTERN 2: Réception simple avec Ref
        if (preg_match('/Vous avez recu (\d+(?:[.,;\s]\d+)*)\s*FCFA.*?Ref\s*:\s*([A-Z0-9]+)/i', $message, $matches)) {
            $amount = $this->parseAmount($matches[1]);
            
            Log::info("✅ PATTERN 2 - Réception simple", [
                'montant' => $amount,
                'ref' => $matches[2]
            ]);
            
            return [
                'transaction_id' => trim($matches[2]),
                'amount' => $amount,
                'network' => 'moov'
            ];
        }

        // PATTERN 3: Votre exemple spécifique - SMS du 20/11/2025
        // "Vous avez recu 3 000 FCFA de l'agent ETS PROINF 2290195135360 le 20/11/2025 20:21:13. Motif : .Nouveau solde 10 251 FCFA. Ref : 030812360189."
        if (preg_match('/Vous avez recu (\d+(?:[.,;\s]\d+)*)\s*FCFA de l\'agent\s+[^\d]*(\d+).*?Ref\s*:\s*([A-Z0-9]+)/i', $message, $matches)) {
            $amount = $this->parseAmount($matches[1]);
            $agentNumber = $matches[2];
            
            Log::info("✅ PATTERN 3 - Format PROINF", [
                'montant' => $amount,
                'agent' => $agentNumber,
                'ref' => $matches[3]
            ]);
            
            return [
                'transaction_id' => trim($matches[3]),
                'amount' => $amount,
                'network' => 'moov',
                'sender_phone' => $agentNumber,
                'receiver_number' => $agentNumber
            ];
        }

        // PATTERN 4: Paiement avec Txn ID
        if (preg_match('/Txn ID:\s*([A-Z0-9]+).*?paye\s*(\d+(?:[.,;\s]\d+)*)\s*FCFA/i', $message, $matches)) {
            $amount = $this->parseAmount($matches[2]);
            
            Log::info("✅ PATTERN 4 - Paiement Txn ID", [
                'montant' => $amount,
                'txn_id' => $matches[1]
            ]);
            
            return [
                'transaction_id' => trim($matches[1]),
                'amount' => $amount,
                'network' => 'moov'
            ];
        }

        // PATTERN 5: Recherche générique de montant + Ref
        if (preg_match('/(\d+(?:[.,;\s]\d+)*)\s*FCFA.*?Ref\s*:\s*([A-Z0-9]{8,20})/i', $message, $matches)) {
            $amount = $this->parseAmount($matches[1]);
            
            Log::info("✅ PATTERN 5 - Générique", [
                'montant' => $amount,
                'ref' => $matches[2]
            ]);
            
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
     * Parser le montant
     */
    private function parseAmount($amountString)
    {
        // Nettoyer le montant: "1 064.00" -> 1064.00, "1;064.00" -> 1064.00
        $cleaned = str_replace([' ', ',', ';', "'"], '', $amountString);
        return floatval($cleaned);
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
     * API pour forcer la synchronisation manuelle
     */
    public function forceSyncSMS(Request $request)
    {
        try {
            Log::info("🔄 SYNCHRONISATION MANUELLE DEMARRÉE");
            $result = $this->syncAllSMSFiles();
            
            // Compter les SMS dans la base
            $smsCount = SMSTransaction::count();
            $recentSMS = SMSTransaction::where('sms_received_at', '>=', now()->subDays(1))->count();
            
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'imported' => $result['imported'],
                'stats' => [
                    'total_sms' => $smsCount,
                    'recent_sms' => $recentSMS
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('💥 Erreur sync manuelle: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
}
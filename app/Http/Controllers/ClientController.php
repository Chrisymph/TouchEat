<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\SMSTransaction;

class ClientController extends Controller
{
    public function dashboard()
    {
        if (!Auth::check() || Auth::user()->role !== 'client') {
            return redirect()->route('client.auth');
        }

        // CORRECTION : Vérifier si le compte est suspendu
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

        // Récupérer le panier depuis la session
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
        // CORRECTION : Vérifier si le compte est suspendu
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
        // CORRECTION : Vérifier si le compte est suspendu
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
        // CORRECTION : Vérifier si le compte est suspendu
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

        // Stocker le réseau sélectionné dans la session pour les commandes sur place
        if ($request->order_type === 'sur_place' && $request->has('network')) {
            session()->put('selected_network', $request->network);
        }

        // Vérifier si on ajoute à une commande existante
        if ($request->has('existing_order_id') && $request->existing_order_id) {
            $order = Order::where('id', $request->existing_order_id)
                ->where('table_number', Auth::user()->table_number)
                ->firstOrFail();

            // Ajouter les articles à la commande existante
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

            // Recalculer le total
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
            // Créer une nouvelle commande avec le bon statut de paiement
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

            // Déterminer l'URL de redirection
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

        // Vérifier si le compte est suspendu
        $user = Auth::user();
        if ($user->isSuspended()) {
            Auth::logout();
            return redirect()->route('client.auth')->with('error', 'Votre compte a été suspendu. Veuillez contacter l\'administrateur.');
        }

        $order = Order::with(['items.menuItem'])
                     ->where('id', $orderId)
                     ->where('table_number', Auth::user()->table_number)
                     ->firstOrFail();

        // Récupérer le réseau sélectionné depuis la session ou la requête
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
     * Traiter la soumission de l'ID de transaction - VERSION SÉCURISÉE
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

        // Vérifier si la commande est déjà payée
        if ($order->payment_status === 'payé') {
            return response()->json([
                'success' => false,
                'message' => 'Cette commande a déjà été payée.'
            ], 400);
        }

        try {
            // VÉRIFICATION RÉELLE - Rechercher la transaction dans la base SMS
            $smsTransaction = SMSTransaction::where('transaction_id', $request->transaction_id)
                ->where('network', $request->network)
                ->where('status', 'pending')
                ->where('sms_received_at', '>=', now()->subHours(24))
                ->first();

            if (!$smsTransaction) {
                \Log::warning("Transaction SMS non trouvée", [
                    'transaction_id' => $request->transaction_id,
                    'network' => $request->network,
                    'order_id' => $orderId
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction non trouvée. Vérifiez l\'ID de transaction ou attendez que le SMS de confirmation arrive.'
                ], 404);
            }

            // Vérifier que le montant correspond
            if (abs($smsTransaction->amount - $order->total) > 1) {
                \Log::warning("Montant ne correspond pas", [
                    'sms_amount' => $smsTransaction->amount,
                    'order_total' => $order->total,
                    'transaction_id' => $request->transaction_id
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Le montant de la transaction (' . number_format($smsTransaction->amount, 0, ',', ' ') . ' FCFA) ne correspond pas au total de la commande (' . number_format($order->total, 0, ',', ' ') . ' FCFA)'
                ], 400);
            }

            // Vérifier que le numéro correspond
            $cleanedPhone = $this->cleanPhoneNumber($request->phone_number);
            $cleanedSender = $this->cleanPhoneNumber($smsTransaction->sender_number);
            
            if ($cleanedPhone !== $cleanedSender) {
                \Log::warning("Numéro téléphone ne correspond pas", [
                    'provided_phone' => $cleanedPhone,
                    'sms_sender' => $cleanedSender,
                    'transaction_id' => $request->transaction_id
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Le numéro de téléphone ne correspond pas à l\'expéditeur du paiement.'
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

            // Créer le paiement
            $payment = Payment::create([
                'order_id' => $order->id,
                'amount' => $order->total,
                'payment_method' => 'mobile_money',
                'transaction_id' => $request->transaction_id,
                'network' => $request->network,
                'phone_number' => $request->phone_number,
                'status' => 'verified',
                'verified_at' => now()
            ]);

            // Marquer la transaction SMS comme utilisée
            $smsTransaction->update([
                'status' => 'used',
                'order_id' => $order->id,
                'verified_at' => now()
            ]);

            // Mettre à jour le statut de la commande
            $order->update(['payment_status' => 'payé']);

            \Log::info("✅ Paiement vérifié avec succès", [
                'order_id' => $order->id,
                'transaction_id' => $request->transaction_id,
                'sms_transaction_id' => $smsTransaction->id,
                'amount' => $order->total
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Paiement vérifié avec succès! Votre commande est en cours de préparation.',
                'auto_verified' => true,
                'redirect_url' => route('client.order.confirmation', $order->id)
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur processTransaction:', [
                'error' => $e->getMessage(), 
                'order_id' => $orderId,
                'transaction_id' => $request->transaction_id ?? 'N/A'
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement du paiement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Nettoyer le numéro de téléphone pour comparaison
     */
    private function cleanPhoneNumber($phone)
    {
        // Supprimer tous les caractères non numériques
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        
        // Si le numéro a 10 chiffres et commence par 225, prendre les 9 derniers
        if (strlen($cleaned) === 10 && substr($cleaned, 0, 3) === '225') {
            $cleaned = substr($cleaned, 3);
        }
        
        // Si le numéro a 9 chiffres, c'est bon
        if (strlen($cleaned) === 9) {
            return $cleaned;
        }
        
        // Si le numéro a 8 chiffres, ajouter le 0
        if (strlen($cleaned) === 8) {
            return '0' . $cleaned;
        }
        
        return $cleaned;
    }

    /**
     * Webhook pour recevoir les SMS de paiement
     */
    public function receiveSMSWebhook(Request $request)
    {
        \Log::info('Webhook SMS reçu:', $request->all());

        try {
            $data = $request->all();
            
            // Validation des données requises
            if (empty($data['from']) || empty($data['text'])) {
                \Log::warning('Webhook SMS: Données manquantes', $data);
                return response()->json(['error' => 'Données manquantes'], 400);
            }

            // Extraire les informations du SMS
            $senderNumber = $data['from'];
            $message = $data['text'];
            $receivedAt = now();

            \Log::info('Analyse du SMS:', [
                'sender' => $senderNumber,
                'message' => $message
            ]);

            // Analyser le message pour détecter un paiement
            $transactionData = $this->parsePaymentSMS($message, $senderNumber);
            
            if ($transactionData) {
                \Log::info('Transaction détectée:', $transactionData);

                // Créer ou mettre à jour la transaction SMS
                $smsTransaction = SMSTransaction::updateOrCreate(
                    [
                        'transaction_id' => $transactionData['transaction_id'],
                        'network' => $transactionData['network']
                    ],
                    [
                        'sender_number' => $senderNumber,
                        'receiver_number' => $transactionData['receiver_number'] ?? 'N/A',
                        'amount' => $transactionData['amount'],
                        'message' => $message,
                        'sms_received_at' => $receivedAt,
                        'status' => 'pending'
                    ]
                );

                \Log::info("Transaction SMS enregistrée: {$smsTransaction->id}");

                // Tenter d'associer automatiquement à une commande
                $autoAssociated = $this->attemptAutoAssociation($smsTransaction);

                if ($autoAssociated) {
                    \Log::info("Transaction auto-associée à la commande: {$smsTransaction->order_id}");
                }

                return response()->json([
                    'success' => true, 
                    'transaction_id' => $smsTransaction->id,
                    'auto_associated' => $autoAssociated
                ]);
            }

            \Log::info('Aucune transaction détectée dans le SMS');
            return response()->json(['success' => false, 'message' => 'Aucune transaction détectée']);

        } catch (\Exception $e) {
            \Log::error('Erreur webhook SMS:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur interne'], 500);
        }
    }

    /**
     * Analyser le SMS pour détecter un paiement
     */
    private function parsePaymentSMS($message, $senderNumber)
    {
        // Nettoyer le message
        $message = trim($message);
        $lowerMessage = strtolower($message);
        
        // Détecter le réseau
        $network = $this->detectNetwork($lowerMessage, $senderNumber);
        
        if (!$network) {
            \Log::info('Réseau non détecté dans le SMS');
            return null;
        }

        \Log::info("Réseau détecté: {$network}");

        // Patterns pour extraire les informations selon le réseau
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
        
        // Détection par préfixe du numéro
        $prefix = substr($senderNumber, 0, 3);
        if (in_array($prefix, ['055', '054', '053', '055', '054', '053'])) {
            return 'mtn';
        } elseif (in_array($prefix, ['057', '058'])) {
            return 'moov';
        } elseif (in_array($prefix, ['077', '078', '077', '078'])) {
            return 'orange';
        }
        
        return null;
    }

    /**
     * Parser les SMS MTN Money
     */
    private function parseMTNSMS($message)
    {
        // Pattern pour MTN Money - Vous avez reçu X FCFA de Y. Ref: Z
        if (preg_match('/Vous avez reçu (\d+(?:[.,]\d+)?)\s*FCFA de (\d+).*?Ref\.? :?\s*([A-Z0-9]+)/i', $message, $matches)) {
            $amount = floatval(str_replace(',', '.', $matches[1]));
            return [
                'transaction_id' => trim($matches[3]),
                'amount' => $amount,
                'network' => 'mtn',
                'receiver_number' => $matches[2]
            ];
        }
        
        // Autre pattern MTN - Transaction ID: X Montant: Y FCFA
        if (preg_match('/Transaction ID:?\s*([A-Z0-9]+).*?Montant:?\s*(\d+(?:[.,]\d+)?)\s*FCFA/i', $message, $matches)) {
            $amount = floatval(str_replace(',', '.', $matches[2]));
            return [
                'transaction_id' => trim($matches[1]),
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
        // Pattern pour Moov Money - Vous avez reçu X FCFA. Ref: Y
        if (preg_match('/Vous avez reçu (\d+(?:[.,]\d+)?)\s*FCFA.*?Ref:?\s*([A-Z0-9]+)/i', $message, $matches)) {
            $amount = floatval(str_replace(',', '.', $matches[1]));
            return [
                'transaction_id' => trim($matches[2]),
                'amount' => $amount,
                'network' => 'moov'
            ];
        }
        
        return null;
    }

    /**
     * Parser les SMS Orange Money
     */
    private function parseOrangeSMS($message)
    {
        // Pattern pour Orange Money - Transaction: X Montant: Y FCFA
        if (preg_match('/Transaction:?\s*([A-Z0-9]+).*?Montant:?\s*(\d+(?:[.,]\d+)?)\s*FCFA/i', $message, $matches)) {
            $amount = floatval(str_replace(',', '.', $matches[2]));
            return [
                'transaction_id' => trim($matches[1]),
                'amount' => $amount,
                'network' => 'orange'
            ];
        }
        
        return null;
    }

    /**
     * Tenter d'associer automatiquement une transaction à une commande
     */
    private function attemptAutoAssociation(SMSTransaction $smsTransaction)
    {
        try {
            // Chercher une commande avec le même montant et statut de paiement en attente
            $order = Order::where('total', $smsTransaction->amount)
                         ->where('payment_status', 'en_attente')
                         ->where('created_at', '>=', now()->subHours(2))
                         ->first();

            if ($order) {
                // Vérifier que la transaction n'est pas déjà utilisée
                $existingPayment = Payment::where('transaction_id', $smsTransaction->transaction_id)
                    ->where('network', $smsTransaction->network)
                    ->where('status', 'verified')
                    ->first();

                if ($existingPayment) {
                    \Log::warning("Transaction déjà utilisée", [
                        'transaction_id' => $smsTransaction->transaction_id,
                        'existing_order_id' => $existingPayment->order_id
                    ]);
                    return false;
                }

                // Associer la transaction à la commande
                $smsTransaction->update([
                    'order_id' => $order->id,
                    'status' => 'used',
                    'verified_at' => now()
                ]);

                // Créer un enregistrement de paiement
                Payment::create([
                    'order_id' => $order->id,
                    'amount' => $smsTransaction->amount,
                    'payment_method' => 'mobile_money',
                    'transaction_id' => $smsTransaction->transaction_id,
                    'network' => $smsTransaction->network,
                    'phone_number' => $smsTransaction->sender_number,
                    'status' => 'verified',
                    'verified_at' => now()
                ]);

                // Mettre à jour le statut de paiement de la commande
                $order->update([
                    'payment_status' => 'payé'
                ]);

                \Log::info("✅ Transaction #{$smsTransaction->id} associée automatiquement à la commande #{$order->id}");
                
                return true;
            }
            
            \Log::info("Aucune commande trouvée pour le montant: {$smsTransaction->amount}");
            return false;

        } catch (\Exception $e) {
            \Log::error('Erreur association automatique:', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Ajouter des articles à une commande existante
     */
    public function addToExistingOrder(Request $request, $orderId)
    {
        // CORRECTION : Vérifier si le compte est suspendu
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

        // CORRECTION : Vérifier si le compte est suspendu
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
        // CORRECTION : Vérifier si le compte est suspendu
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

        // Vérifier si la commande peut être livrée
        if ($order->status === 'terminé' || $order->status === 'prêt') {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de demander la livraison pour une commande déjà prête ou terminée.'
            ]);
        }

        // Mettre à jour le type de commande en livraison
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

        // CORRECTION : Vérifier si le compte est suspendu
        $user = Auth::user();
        if ($user->isSuspended()) {
            Auth::logout();
            return redirect()->route('client.auth')->with('error', 'Votre compte a été suspendu. Veuillez contacter l\'administrateur.');
        }

        $user = Auth::user();
        
        // Récupérer TOUTES les commandes (sans filtre de paiement)
        $orders = Order::where('table_number', $user->table_number)
            ->with('items.menuItem')
            ->orderBy('created_at', 'desc')
            ->get();

        // Debug dans la vue
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
}
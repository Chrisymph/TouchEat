<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Carbon\Carbon;

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

        // Vérifier si on ajoute à une commande existante via URL
        $isAddingToExisting = request()->has('order_id') && request('order_id');
        $existingOrderId = $isAddingToExisting ? request('order_id') : null;

        return view('client.dashboard', [
            'tableNumber' => Auth::user()->table_number,
            'menuItems' => $menuItems,
            'currentOrder' => $currentOrder,
            'cartItems' => $cartItems,
            'cartCount' => $cartCount,
            'isAddingToExisting' => $isAddingToExisting,
            'existingOrderId' => $existingOrderId
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

        // Vérifier que la commande existe et appartient à l'utilisateur
        if ($request->order_id) {
            $order = Order::where('id', $request->order_id)
                ->where('table_number', Auth::user()->table_number)
                ->first();
            
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvée ou non autorisée.'
                ], 404);
            }
        }

        $cart = session()->get('cart', []);
        $menuItem = MenuItem::find($request->menu_item_id);

        if (!$menuItem || !$menuItem->available) {
            return response()->json([
                'success' => false,
                'message' => 'Article non disponible.'
            ], 404);
        }

        $cartKey = $request->menu_item_id . ($request->order_id ? '_' . $request->order_id : '');

        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['quantity'] += $request->quantity;
        } else {
            $cart[$cartKey] = [
                'id' => $menuItem->id,
                'name' => $menuItem->name,
                'description' => $menuItem->description,
                'price' => $menuItem->price,
                'quantity' => $request->quantity,
                'category' => $menuItem->category,
                'promotion_discount' => $menuItem->promotion_discount,
                'original_price' => $menuItem->original_price,
                'order_id' => $request->order_id,
                'cart_key' => $cartKey
            ];
        }

        session()->put('cart', $cart);

        $cartCount = array_sum(array_column($cart, 'quantity'));

        return response()->json([
            'success' => true,
            'cart_count' => $cartCount,
            'cart_items' => array_values($cart),
            'has_existing_order' => !empty($request->order_id),
            'message' => 'Article ajouté au panier avec succès!'
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
            'quantity' => 'required|integer|min:0',
            'order_id' => 'nullable|exists:orders,id'
        ]);

        $cart = session()->get('cart', []);
        $cartKey = $request->menu_item_id . ($request->order_id ? '_' . $request->order_id : '');

        if ($request->quantity == 0) {
            unset($cart[$cartKey]);
        } else {
            if (isset($cart[$cartKey])) {
                $cart[$cartKey]['quantity'] = $request->quantity;
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
            'cart_items' => array_values($cart),
            'message' => 'Panier mis à jour avec succès!'
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
            'network' => 'required|in:mtn,moov,celtis',
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

        // Filtrer les articles selon la commande existante
        $filteredCart = [];
        foreach ($cart as $item) {
            if ($request->has('existing_order_id') && $request->existing_order_id) {
                // Si on ajoute à une commande existante, ne prendre que les articles de cette commande
                if (isset($item['order_id']) && $item['order_id'] == $request->existing_order_id) {
                    $filteredCart[$item['id']] = $item;
                }
            } else {
                // Si nouvelle commande, ne prendre que les articles sans order_id
                if (!isset($item['order_id'])) {
                    $filteredCart[$item['id']] = $item;
                }
            }
        }

        if (empty($filteredCart)) {
            return response()->json([
                'success' => false, 
                'message' => 'Aucun article valide dans le panier pour ce type de commande'
            ]);
        }

        $total = 0;
        foreach ($filteredCart as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        // Stocker le réseau sélectionné dans la session
        session()->put('selected_network', $request->network);

        if ($request->has('existing_order_id') && $request->existing_order_id) {
            $order = Order::where('id', $request->existing_order_id)
                ->where('table_number', Auth::user()->table_number)
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvée'
                ], 404);
            }

            // Calculer seulement le total des nouveaux articles
            $newItemsTotal = 0;
            foreach ($filteredCart as $menuItemId => $item) {
                $newItemsTotal += $item['price'] * $item['quantity'];
            }

            // Créer un nouveau paiement (en attente) pour les nouveaux articles uniquement
            $additionalPayment = Payment::create([
                'order_id' => $order->id,
                'amount' => $newItemsTotal,
                'payment_method' => 'mobile_money',
                'status' => 'en_attente',
                'network' => $request->network,
                'phone_number' => $request->phone_number,
                'transaction_id' => 'pending_' . time() . '_' . rand(1000, 9999)
            ]);

            // Ajouter les articles à la commande existante
            foreach ($filteredCart as $menuItemId => $item) {
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

            // Mettre à jour le total de la commande pour l'affichage
            $newTotal = OrderItem::where('order_id', $order->id)
                ->get()
                ->sum(function($item) {
                    return $item->unit_price * $item->quantity;
                });

            $order->total = $newTotal;
            $order->save();

            // Supprimer seulement les articles de cette commande du panier
            foreach ($cart as $key => $item) {
                if (isset($item['order_id']) && $item['order_id'] == $request->existing_order_id) {
                    unset($cart[$key]);
                }
            }
            session()->put('cart', $cart);

            // Redirection vers le paiement KKiaPay (paiement additionnel)
            $redirectUrl = route('client.kkiapay.show', [
                'order' => $order->id,
                'payment' => $additionalPayment->id
            ]);

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'payment_id' => $additionalPayment->id,
                'estimated_time' => $order->estimated_time,
                'message' => 'Articles ajoutés à la commande existante avec succès!',
                'redirect_url' => $redirectUrl,
                'amount_to_pay' => $newItemsTotal
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
                'started_at' => null,
                'delivery_address' => $request->delivery_address,
                'delivery_notes' => $request->delivery_notes
            ]);

            foreach ($filteredCart as $menuItemId => $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'menu_item_id' => $menuItemId,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'category' => $item['category'],
                    'notes' => ''
                ]);
            }

            // Supprimer seulement les articles sans order_id du panier
            foreach ($cart as $key => $item) {
                if (!isset($item['order_id'])) {
                    unset($cart[$key]);
                }
            }
            session()->put('cart', $cart);

            // Paiement en attente pour la nouvelle commande
            $payment = Payment::create([
                'order_id' => $order->id,
                'amount' => $total,
                'payment_method' => 'mobile_money',
                'status' => 'en_attente',
                'network' => $request->network,
                'phone_number' => $request->phone_number,
                'transaction_id' => 'pending_' . time() . '_' . rand(1000, 9999)
            ]);

            // Redirection vers le paiement KKiaPay
            $redirectUrl = route('client.kkiapay.show', [
                'order' => $order->id,
                'payment' => $payment->id
            ]);

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

        // Calculer le temps écoulé depuis le début de la préparation
        $elapsedMinutes = 0;
        $startedAt = null;
        
        if ($order->status === 'en_cours' && $order->estimated_time && $order->started_at) {
            // Convertir started_at en objet Carbon si c'est une string
            $startedAt = $order->started_at instanceof Carbon ? $order->started_at : Carbon::parse($order->started_at);
            $elapsedMinutes = now()->diffInMinutes($startedAt);
            
            // Limiter au temps estimé si dépassé
            if ($elapsedMinutes > $order->estimated_time) {
                $elapsedMinutes = $order->estimated_time;
            }
        }

        // Formatage correct pour JavaScript
        $startedAtFormatted = $startedAt ? $startedAt->toISOString() : null;

        return view('client.order-confirmation', [
            'tableNumber' => Auth::user()->table_number,
            'order' => $order,
            'elapsedMinutes' => $elapsedMinutes,
            'startedAt' => $startedAtFormatted
        ]);
    }

    public function getOrderStatus($orderId)
    {
        $order = Order::with('items.menuItem')->find($orderId);
        
        if (!$order) {
            return response()->json(['error' => 'Commande non trouvée'], 404);
        }

        // Calculer le temps écoulé depuis le début de la préparation
        $elapsedMinutes = 0;
        $startedAt = null;
        
        if ($order->status === 'en_cours' && $order->started_at) {
            // Convertir started_at en objet Carbon si c'est une string
            $startedAt = $order->started_at instanceof Carbon ? $order->started_at : Carbon::parse($order->started_at);
            $elapsedMinutes = now()->diffInMinutes($startedAt);
            
            // Limiter au temps estimé si dépassé
            if ($order->estimated_time && $elapsedMinutes > $order->estimated_time) {
                $elapsedMinutes = $order->estimated_time;
            }
        }

        return response()->json([
            'status' => $order->status,
            'estimated_time' => $order->estimated_time,
            'marked_ready_at' => $order->marked_ready_at,
            'payment_status' => $order->payment_status,
            'started_at' => $startedAt ? $startedAt->toISOString() : null,
            'elapsed_minutes' => $elapsedMinutes,
            'remaining_minutes' => $order->estimated_time ? max(0, $order->estimated_time - $elapsedMinutes) : 0
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
     * Vider le panier
     */
    public function clearCart(Request $request)
    {
        $cart = session()->get('cart', []);
        $orderId = $request->get('order_id');
        
        if ($orderId) {
            // Vider seulement les articles d'une commande spécifique
            foreach ($cart as $key => $item) {
                if (isset($item['order_id']) && $item['order_id'] == $orderId) {
                    unset($cart[$key]);
                }
            }
        } else {
            // Vider tout le panier
            $cart = [];
        }
        
        session()->put('cart', $cart);
        
        return response()->json([
            'success' => true,
            'message' => 'Panier vidé avec succès',
            'cart_count' => array_sum(array_column($cart, 'quantity'))
        ]);
    }
}
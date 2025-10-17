<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;

class ClientController extends Controller
{
    public function dashboard()
    {
        if (!Auth::check() || Auth::user()->role !== 'client') {
            return redirect()->route('client.auth');
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
        $request->validate([
            'menu_item_id' => 'required|exists:menu_items,id',
            'quantity' => 'required|integer|min:1'
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
                'original_price' => $menuItem->original_price
            ];
        }

        session()->put('cart', $cart);

        $cartCount = array_sum(array_column($cart, 'quantity'));

        return response()->json([
            'success' => true,
            'cart_count' => $cartCount,
            'cart_items' => array_values($cart)
        ]);
    }

    public function updateCart(Request $request)
    {
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
        $request->validate([
            'order_type' => 'required|in:sur_place,livraison',
            'phone_number' => 'required|string'
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

        $order = Order::create([
            'table_number' => Auth::user()->table_number,
            'total' => $total,
            'status' => 'commandé',
            'payment_status' => 'en_attente',
            'order_type' => $request->order_type,
            'customer_phone' => $request->phone_number,
            'estimated_time' => null
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

        return response()->json([
            'success' => true,
            'order_id' => $order->id,
            'estimated_time' => $order->estimated_time,
            'message' => 'Commande passée avec succès!',
            'redirect_url' => route('client.order.confirmation', $order->id)
        ]);
    }

    /**
     * Ajouter des articles à une commande existante
     */
    public function addToExistingOrder(Request $request, $orderId)
    {
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
            'marked_ready_at' => $order->marked_ready_at
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
            'orders' => $orders, // Afficher TOUTES les commandes
            'tableNumber' => $user->table_number,
            'debug' => $debug
        ]);
    }
}
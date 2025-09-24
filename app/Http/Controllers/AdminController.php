<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\MenuItem;

class AdminController extends Controller
{
    /**
     * Afficher le tableau de bord administrateur
     */
    public function dashboard()
    {
        $today = Carbon::today();
        
        // Statistiques
        $stats = [
            'todayOrders' => Order::whereDate('created_at', $today)->count(),
            'pendingOrders' => Order::whereIn('status', ['commandé', 'en_cours'])->count(),
            'todayRevenue' => Order::whereDate('created_at', $today)->sum('total'),
            'activeTables' => Order::whereIn('status', ['commandé', 'en_cours', 'prêt'])
                                ->distinct('table_number')
                                ->count('table_number'),
        ];

        // Commandes récentes
        $recentOrders = Order::with('items')
                           ->orderBy('created_at', 'desc')
                           ->limit(5)
                           ->get();

        return view('admin.dashboard', compact('stats', 'recentOrders'));
    }

    /**
     * Gestion des commandes
     */
    public function orders(Request $request)
    {
        $status = $request->get('status', 'pending');
        
        $query = Order::with('items');
        
        switch ($status) {
            case 'pending':
                $query->whereIn('status', ['commandé', 'en_cours']);
                break;
            case 'ready':
                $query->where('status', 'prêt');
                break;
            case 'completed':
                $query->whereIn('status', ['livré', 'terminé']);
                break;
        }
        
        $orders = $query->orderBy('created_at', 'desc')->get();
        
        $orderCounts = [
            'pending' => Order::whereIn('status', ['commandé', 'en_cours'])->count(),
            'ready' => Order::where('status', 'prêt')->count(),
            'completed' => Order::whereIn('status', ['livré', 'terminé'])->count(),
        ];

        return view('admin.orders', compact('orders', 'status', 'orderCounts'));
    }

    /**
     * Mettre à jour le statut d'une commande
     */
    public function updateOrderStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:commandé,en_cours,prêt,livré,terminé'
        ]);

        $order = Order::findOrFail($id);
        $order->status = $request->status;
        
        if ($request->status === 'prêt') {
            $order->marked_ready_at = now();
        }
        
        $order->save();

        return redirect()->back()->with('success', 'Statut de la commande mis à jour!');
    }

    /**
     * Afficher les détails d'une commande
     */
    public function showOrder($id)
    {
        $order = Order::with('items')->findOrFail($id);
        return view('admin.order-details', compact('order'));
    }

    /**
     * Gestion du menu
     */
    public function menu(Request $request)
    {
        $category = $request->get('category', 'repas');
        
        $menuItems = MenuItem::where('category', $category)
                           ->orderBy('name')
                           ->get();
                           
        $categories = [
            'repas' => MenuItem::where('category', 'repas')->count(),
            'boisson' => MenuItem::where('category', 'boisson')->count(),
        ];

        return view('admin.menu', compact('menuItems', 'category', 'categories'));
    }

    /**
     * Ajouter un nouvel article au menu
     */
    public function addMenuItem(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'category' => 'required|in:repas,boisson',
            'available' => 'boolean',
        ]);

        MenuItem::create([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'category' => $request->category,
            'available' => $request->available ?? true,
        ]);

        return redirect()->back()->with('success', 'Article ajouté au menu!');
    }

    /**
     * Mettre à jour un article du menu
     */
    public function updateMenuItem(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'category' => 'required|in:repas,boisson',
            'available' => 'boolean',
        ]);

        $menuItem = MenuItem::findOrFail($id);
        $menuItem->update($request->all());

        return redirect()->back()->with('success', 'Article mis à jour!');
    }

    /**
     * Supprimer un article du menu
     */
    public function deleteMenuItem($id)
    {
        $menuItem = MenuItem::findOrFail($id);
        $menuItem->delete();

        return redirect()->back()->with('success', 'Article supprimé!');
    }

    /**
     * Basculer la disponibilité d'un article
     */
    public function toggleMenuItemAvailability($id)
    {
        $menuItem = MenuItem::findOrFail($id);
        $menuItem->available = !$menuItem->available;
        $menuItem->save();

        return redirect()->back()->with('success', 'Disponibilité mise à jour!');
    }

    /**
     * Ajouter une promotion à un article
     */
    public function addPromotion(Request $request, $id)
    {
        $request->validate([
            'discount' => 'required|numeric|min:1|max:99',
            'original_price' => 'required|numeric|min:0',
        ]);

        $menuItem = MenuItem::findOrFail($id);
        
        // Calculer le nouveau prix avec la promotion
        $discountedPrice = $request->original_price * (1 - ($request->discount / 100));
        
        $menuItem->update([
            'price' => $discountedPrice,
            'promotion_discount' => $request->discount,
            'original_price' => $request->original_price,
        ]);

        return redirect()->back()->with('success', 'Promotion appliquée!');
    }

    /**
     * Supprimer une promotion
     */
    public function removePromotion($id)
    {
        $menuItem = MenuItem::findOrFail($id);
        
        $menuItem->update([
            'price' => $menuItem->original_price,
            'promotion_discount' => null,
            'original_price' => null,
        ]);

        return redirect()->back()->with('success', 'Promotion supprimée!');
    }

    /**
     * Rapports et statistiques
     */
    public function reports()
    {
        $startDate = request('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = request('end_date', now()->format('Y-m-d'));

        $reports = [
            'totalRevenue' => Order::whereBetween('created_at', [$startDate, $endDate])
                                 ->sum('total'),
            'totalOrders' => Order::whereBetween('created_at', [$startDate, $endDate])
                                ->count(),
            'averageOrderValue' => Order::whereBetween('created_at', [$startDate, $endDate])
                                      ->avg('total'),
            'popularItems' => DB::table('order_items')
                              ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
                              ->whereBetween('order_items.created_at', [$startDate, $endDate])
                              ->select('menu_items.name', DB::raw('SUM(order_items.quantity) as total_sold'))
                              ->groupBy('menu_items.id', 'menu_items.name')
                              ->orderBy('total_sold', 'desc')
                              ->limit(10)
                              ->get(),
        ];

        return view('admin.reports', compact('reports', 'startDate', 'endDate'));
    }

    /**
     * Déconnexion administrateur
     */
    public function logout()
    {
        Auth::logout();
        return redirect('/admin-auth')->with('success', 'Déconnexion réussie!');
    }
}
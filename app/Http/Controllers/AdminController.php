<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\MenuItem;
use App\Models\Report;
use App\Models\OrderItem;

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
     * Gestion des commandes (version complète)
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
     * Gestion des commandes (version AJAX pour le dashboard)
     */
    public function ordersAjax(Request $request)
    {
        $status = $request->get('status', 'pending');
        
        $query = Order::with(['items.menuItem']);
        
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

        return view('admin.orders-content', compact('orders', 'status', 'orderCounts'));
    }

    /**
     * Gestion du menu (version AJAX pour le dashboard)
     */
    public function menuAjax(Request $request)
    {
        $category = $request->get('category', 'repas');
        
        $menuItems = MenuItem::where('category', $category)
                           ->orderBy('name')
                           ->get();
                           
        $categories = [
            'repas' => MenuItem::where('category', 'repas')->count(),
            'boisson' => MenuItem::where('category', 'boisson')->count(),
        ];

        return view('admin.menu-content', compact('menuItems', 'category', 'categories'));
    }

    /**
     * Récupérer un article du menu via AJAX
     */
    public function getMenuItem($id)
    {
        try {
            $menuItem = MenuItem::findOrFail($id);
            
            return response()->json([
                'id' => $menuItem->id,
                'name' => $menuItem->name,
                'price' => $menuItem->price,
                'description' => $menuItem->description,
                'category' => $menuItem->category,
                'available' => $menuItem->available
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Article non trouvé'
            ], 404);
        }
    }

    /**
     * Rapports et statistiques (version AJAX pour le dashboard)
     */
    public function reportsAjax(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        // Commandes terminées pour l'analyse
        $completedOrders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['terminé', 'livré'])
            ->with('items')
            ->get();

        // Calcul des statistiques
        $totalRevenue = $completedOrders->sum('total');
        $totalOrders = $completedOrders->count();
        $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        // Commandes par type
        $dineInOrders = $completedOrders->where('order_type', 'sur_place')->count();
        $deliveryOrders = $completedOrders->where('order_type', 'livraison')->count();

        // Performance du menu
        $menuPerformance = $this->getMenuPerformance($completedOrders);
        $topItems = $this->getTopItems($menuPerformance);

        // Temps de préparation moyen
        $avgPreparationTime = $this->getAveragePreparationTime($completedOrders);

        // Statistiques détaillées
        $detailedStats = $this->getDetailedStats($startDate, $endDate);

        return view('admin.reports-content', compact(
            'totalRevenue',
            'totalOrders',
            'avgOrderValue',
            'dineInOrders',
            'deliveryOrders',
            'topItems',
            'avgPreparationTime',
            'detailedStats',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Performance du menu
     */
    private function getMenuPerformance($orders)
    {
        $performance = [];

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $name = $item->name;
                
                if (!isset($performance[$name])) {
                    $performance[$name] = [
                        'name' => $name,
                        'category' => $item->category,
                        'totalQuantity' => 0,
                        'totalRevenue' => 0,
                        'orders' => 0
                    ];
                }

                $performance[$name]['totalQuantity'] += $item->quantity;
                $performance[$name]['totalRevenue'] += $item->price * $item->quantity;
                $performance[$name]['orders'] += 1;
            }
        }

        return $performance;
    }

    /**
     * Top articles
     */
    private function getTopItems($menuPerformance)
    {
        return collect($menuPerformance)
            ->sortByDesc('totalRevenue')
            ->take(5)
            ->values()
            ->all();
    }

    /**
     * Temps de préparation moyen
     */
    private function getAveragePreparationTime($orders)
    {
        $ordersWithTime = $orders->filter(function($order) {
            return !is_null($order->estimated_time);
        });

        if ($ordersWithTime->count() === 0) {
            return 0;
        }

        return $ordersWithTime->avg('estimated_time');
    }

    /**
     * Statistiques détaillées
     */
    private function getDetailedStats($startDate, $endDate)
    {
        $allOrders = Order::whereBetween('created_at', [$startDate, $endDate])->get();
        $completedOrders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['terminé', 'livré'])
            ->with('items')
            ->get();

        // Commandes par statut
        $ordersByStatus = [
            'terminé' => $allOrders->where('status', 'terminé')->count(),
            'livré' => $allOrders->where('status', 'livré')->count(),
            'en_cours' => $allOrders->where('status', 'en_cours')->count(),
            'prêt' => $allOrders->where('status', 'prêt')->count(),
        ];

        // Analyse des revenus
        $revenueAnalysis = [
            'sur_place' => $completedOrders->where('order_type', 'sur_place')->sum('total'),
            'livraison' => $completedOrders->where('order_type', 'livraison')->sum('total'),
        ];

        // CORRECTION : Utiliser la nouvelle colonne category
        $menuItems = OrderItem::whereHas('order', function($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate])
                      ->whereIn('status', ['terminé', 'livré']);
            })
            ->select('category', DB::raw('SUM(quantity) as total_quantity'))
            ->groupBy('category')
            ->get()
            ->pluck('total_quantity', 'category');

        $menuPerformance = [
            'repas' => $menuItems['repas'] ?? 0,
            'boisson' => $menuItems['boisson'] ?? 0,
        ];

        return [
            'ordersByStatus' => $ordersByStatus,
            'revenueAnalysis' => $revenueAnalysis,
            'menuPerformance' => $menuPerformance,
        ];
    }

    /**
     * Sauvegarder un rapport
     */
    public function saveReport(Request $request)
    {
        try {
            $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
            $endDate = $request->get('end_date', now()->format('Y-m-d'));

            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
            
            // Déterminer le type de rapport
            $type = 'custom';
            if ($start->eq($end)) {
                $type = 'daily';
            } elseif ($start->diffInDays($end) === 6) { // Semaine complète
                $type = 'weekly';
            } elseif ($start->day === 1 && $end->isLastOfMonth()) { // Mois complet
                $type = 'monthly';
            }

            $report = Report::create([
                'name' => Report::generateName($type, $start, $end),
                'type' => $type,
                'start_date' => $start,
                'end_date' => $end,
                'data' => ['message' => 'Rapport sauvegardé depuis le dashboard'],
                'description' => 'Rapport généré automatiquement depuis le dashboard',
                'is_generated' => true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Rapport sauvegardé avec succès!',
                'report' => $report
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde du rapport: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API pour les données de graphique
     */
    public function reportsChartData(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $completedOrders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['terminé', 'livré'])
            ->with('items')
            ->get();

        $menuPerformance = $this->getMenuPerformance($completedOrders);
        $topItems = $this->getTopItems($menuPerformance);

        // Données par défaut si pas de données
        if (empty($topItems)) {
            $topItems = [
                ['name' => 'Pizza', 'category' => 'repas', 'totalRevenue' => 120, 'totalQuantity' => 15, 'orders' => 12],
                ['name' => 'Burger', 'category' => 'repas', 'totalRevenue' => 95, 'totalQuantity' => 12, 'orders' => 10],
                ['name' => 'Coca', 'category' => 'boisson', 'totalRevenue' => 45, 'totalQuantity' => 20, 'orders' => 15],
                ['name' => 'Pâtes', 'category' => 'repas', 'totalRevenue' => 80, 'totalQuantity' => 10, 'orders' => 8],
                ['name' => 'Eau', 'category' => 'boisson', 'totalRevenue' => 30, 'totalQuantity' => 25, 'orders' => 18]
            ];
        }

        return response()->json([
            'topItems' => $topItems
        ]);
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
     * Gestion du menu (version complète)
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
        \Log::info('=== DÉBUT AJOUT ARTICLE ===');
        \Log::info('Données reçues:', $request->all());

        try {
            // Validation des données
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'price' => 'required|numeric|min:0',
                'category' => 'required|in:repas,boisson',
                'available' => 'sometimes|boolean',
            ]);

            \Log::info('Validation réussie:', $validated);

            // Préparer les données SANS la colonne image
            $menuData = [
                'name' => $validated['name'],
                'description' => $validated['description'],
                'price' => $validated['price'],
                'category' => $validated['category'],
                'available' => $request->has('available') ? (bool)$request->available : true,
                'promotion_discount' => null,
                'original_price' => null,
            ];

            \Log::info('Données préparées pour insertion:', $menuData);

            // Créer l'article
            $menuItem = MenuItem::create($menuData);

            \Log::info('Article créé avec succès:', ['id' => $menuItem->id]);

            // Si c'est une requête AJAX, retourner JSON
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Article ajouté au menu!',
                    'item' => $menuItem
                ]);
            }

            return redirect()->back()->with('success', 'Article ajouté au menu!');

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la sauvegarde:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la sauvegarde: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Erreur lors de la sauvegarde: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Mettre à jour un article du menu
     */
    public function updateMenuItem(Request $request, $id)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'price' => 'required|numeric|min:0',
                'category' => 'required|in:repas,boisson',
                'available' => 'boolean',
            ]);

            $menuItem = MenuItem::findOrFail($id);
            $menuItem->update($request->all());

            // Si c'est une requête AJAX, retourner JSON
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Article mis à jour!',
                    'item' => $menuItem
                ]);
            }

            return redirect()->back()->with('success', 'Article mis à jour!');

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la mise à jour:', [
                'message' => $e->getMessage()
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Supprimer un article du menu
     */
    public function deleteMenuItem($id)
    {
        try {
            $menuItem = MenuItem::findOrFail($id);
            $menuItem->delete();

            return response()->json([
                'success' => true,
                'message' => 'Article supprimé avec succès!'
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la suppression:', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Basculer la disponibilité d'un article
     */
    public function toggleMenuItemAvailability($id)
    {
        try {
            $menuItem = MenuItem::findOrFail($id);
            $menuItem->available = !$menuItem->available;
            $menuItem->save();

            return response()->json([
                'success' => true,
                'message' => 'Disponibilité mise à jour!',
                'available' => $menuItem->available
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ajouter une promotion à un article
     */
    public function addPromotion(Request $request, $id)
    {
        try {
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

            return response()->json([
                'success' => true,
                'message' => 'Promotion appliquée!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une promotion
     */
    public function removePromotion($id)
    {
        try {
            $menuItem = MenuItem::findOrFail($id);
            
            $menuItem->update([
                'price' => $menuItem->original_price,
                'promotion_discount' => null,
                'original_price' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Promotion supprimée!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rapports et statistiques (version page complète)
     */
    public function reports(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        // Commandes terminées pour l'analyse
        $completedOrders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['terminé', 'livré'])
            ->with('items')
            ->get();

        // Calcul des statistiques
        $totalRevenue = $completedOrders->sum('total');
        $totalOrders = $completedOrders->count();
        $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        // Commandes par type
        $dineInOrders = $completedOrders->where('order_type', 'sur_place')->count();
        $deliveryOrders = $completedOrders->where('order_type', 'livraison')->count();

        // Performance du menu
        $menuPerformance = $this->getMenuPerformance($completedOrders);
        $topItems = $this->getTopItems($menuPerformance);

        // Temps de préparation moyen
        $avgPreparationTime = $this->getAveragePreparationTime($completedOrders);

        // Statistiques détaillées
        $detailedStats = $this->getDetailedStats($startDate, $endDate);

        return view('admin.reports-full', compact(
            'totalRevenue',
            'totalOrders',
            'avgOrderValue',
            'dineInOrders',
            'deliveryOrders',
            'topItems',
            'avgPreparationTime',
            'detailedStats',
            'startDate',
            'endDate'
        ));
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
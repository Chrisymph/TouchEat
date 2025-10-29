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
use App\Models\User;

class AdminController extends Controller
{
    /**
     * Afficher le tableau de bord administrateur
     */
    public function dashboard()
    {
        $today = Carbon::today();
        $admin = Auth::user();
        
        // CORRECTION : Récupérer seulement les tables des clients liés
        $linkedClientTables = $admin->linkedClients()->pluck('table_number')->filter()->toArray();
        
        // Statistiques - CORRECTION : Filtrer par tables liées
        $stats = [
            'todayOrders' => Order::whereIn('table_number', $linkedClientTables)
                                ->whereDate('created_at', $today)->count(),
            'pendingOrders' => Order::whereIn('table_number', $linkedClientTables)
                                ->whereIn('status', ['commandé', 'en_cours'])->count(),
            'todayRevenue' => Order::whereIn('table_number', $linkedClientTables)
                                ->whereDate('created_at', $today)->sum('total'),
            'activeTables' => Order::whereIn('table_number', $linkedClientTables)
                                ->whereIn('status', ['commandé', 'en_cours', 'prêt'])
                                ->distinct('table_number')
                                ->count('table_number'),
        ];

        // Commandes récentes - CORRECTION : Filtrer par tables liées
        $recentOrders = Order::with('items')
                           ->whereIn('table_number', $linkedClientTables)
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
        $admin = Auth::user();
        
        // CORRECTION : Récupérer seulement les tables des clients liés
        $linkedClientTables = $admin->linkedClients()->pluck('table_number')->filter()->toArray();
        
        $query = Order::with('items')
                    ->whereIn('table_number', $linkedClientTables);
        
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
            'pending' => Order::whereIn('table_number', $linkedClientTables)
                            ->whereIn('status', ['commandé', 'en_cours'])->count(),
            'ready' => Order::whereIn('table_number', $linkedClientTables)
                            ->where('status', 'prêt')->count(),
            'completed' => Order::whereIn('table_number', $linkedClientTables)
                            ->whereIn('status', ['livré', 'terminé'])->count(),
        ];

        return view('admin.orders', compact('orders', 'status', 'orderCounts'));
    }

    /**
     * Gestion des commandes (version AJAX pour le dashboard)
     */
    public function ordersAjax(Request $request)
    {
        $status = $request->get('status', 'pending');
        $admin = Auth::user();
        
        // CORRECTION : Récupérer seulement les tables des clients liés
        $linkedClientTables = $admin->linkedClients()->pluck('table_number')->filter()->toArray();
        
        $query = Order::with(['items.menuItem'])
                    ->whereIn('table_number', $linkedClientTables);
        
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
            'pending' => Order::whereIn('table_number', $linkedClientTables)
                            ->whereIn('status', ['commandé', 'en_cours'])->count(),
            'ready' => Order::whereIn('table_number', $linkedClientTables)
                            ->where('status', 'prêt')->count(),
            'completed' => Order::whereIn('table_number', $linkedClientTables)
                            ->whereIn('status', ['livré', 'terminé'])->count(),
        ];

        return view('admin.orders-content', compact('orders', 'status', 'orderCounts'));
    }

    /**
     * Afficher les détails d'une commande (CORRIGÉ)
     */
    public function showOrder($id)
    {
        try {
            $order = Order::with(['items.menuItem'])->findOrFail($id);
            
            // DÉTECTION CORRECTE DES REQUÊTES AJAX
            if (request()->ajax() || request()->wantsJson() || str_contains(request()->url(), '/ajax')) {
                return response()->json([
                    'success' => true,
                    'order' => [
                        'id' => $order->id,
                        'table_number' => $order->table_number ?? 'N/A',
                        'order_type' => $order->order_type ?? 'sur_place',
                        'status' => $order->status ?? 'commandé',
                        'payment_status' => $order->payment_status ?? 'Non payé',
                        'customer_phone' => $order->customer_phone ?? 'Non renseigné',
                        'created_at' => $order->created_at->format('d/m/Y H:i'),
                        'estimated_time' => $order->estimated_time ? $order->estimated_time . ' minutes' : 'Non défini',
                        'total' => number_format($order->total, 0, ',', ' ') . ' FCFA',
                        'items' => $order->items->map(function($item) {
                            return [
                                'name' => $item->menuItem->name ?? $item->name ?? 'Article inconnu',
                                'quantity' => $item->quantity,
                                'price' => $item->unit_price,
                                'total' => $item->unit_price * $item->quantity
                            ];
                        })
                    ]
                ]);
            }
            
            // Si pas AJAX, retourner une vue (même si vous ne l'avez pas)
            return response()->json([
                'error' => 'Page non disponible. Utilisez le modal.'
            ], 404);
            
        } catch (\Exception $e) {
            \Log::error('Erreur showOrder:', ['id' => $id, 'error' => $e->getMessage()]);
            
            if (request()->ajax() || request()->wantsJson() || str_contains(request()->url(), '/ajax')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvée: ' . $e->getMessage()
                ], 404);
            }
            
            return response()->json([
                'error' => 'Commande non trouvée'
            ], 404);
        }
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
     * Gestion des clients (version AJAX pour le dashboard)
     */
    public function clientsAjax(Request $request)
    {
        try {
            // Récupérer les clients liés à cet admin
            $linkedClients = Auth::user()->linkedClients()
                ->orderBy('name')
                ->get();

            // Récupérer tous les clients disponibles pour l'ajout
            $availableClients = User::where('role', 'client')
                ->whereDoesntHave('linkedAdmins', function($query) {
                    $query->where('admin_id', Auth::id());
                })
                ->orderBy('name')
                ->get();

            return view('admin.clients-content', compact('linkedClients', 'availableClients'));

        } catch (\Exception $e) {
            \Log::error('Erreur clientsAjax:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des clients'
            ], 500);
        }
    }

    /**
     * Récupérer les clients disponibles pour l'ajout
     */
    public function getAvailableClients()
    {
        try {
            $availableClients = User::where('role', 'client')
                ->whereDoesntHave('linkedAdmins', function($query) {
                    $query->where('admin_id', Auth::id());
                })
                ->orderBy('name')
                ->get()
                ->map(function($client) {
                    return [
                        'id' => $client->id,
                        'name' => $client->name,
                        'email' => $client->email,
                        'table_number' => $client->table_number ?? 'N/A',
                        'is_suspended' => $client->is_suspended ?? false
                    ];
                });

            return response()->json([
                'success' => true,
                'clients' => $availableClients
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur getAvailableClients:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des clients disponibles'
            ], 500);
        }
    }

    /**
     * Lier des clients à l'admin
     */
    public function linkClients(Request $request)
    {
        try {
            $request->validate([
                'client_ids' => 'required|array',
                'client_ids.*' => 'exists:users,id'
            ]);

            $admin = Auth::user();
            $clientIds = $request->client_ids;

            // Vérifier que les clients existent et sont bien des clients
            $clients = User::whereIn('id', $clientIds)
                ->where('role', 'client')
                ->get();

            // Lier les clients à l'admin
            $admin->linkedClients()->syncWithoutDetaching($clients->pluck('id')->toArray());

            return response()->json([
                'success' => true,
                'message' => count($clients) . ' client(s) lié(s) avec succès!'
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur linkClients:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la liaison des clients: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retirer un client lié
     */
    public function unlinkClient($clientId)
    {
        try {
            $admin = Auth::user();
            
            // Vérifier que le client est bien lié à cet admin
            if (!$admin->linkedClients()->where('client_id', $clientId)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce client n\'est pas lié à votre compte'
                ], 404);
            }

            $admin->linkedClients()->detach($clientId);

            return response()->json([
                'success' => true,
                'message' => 'Client retiré avec succès!'
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur unlinkClient:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du retrait du client: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Suspendre un client
     */
    public function suspendClient($clientId)
    {
        try {
            $admin = Auth::user();
            
            // CORRECTION : Récupérer le client directement depuis User
            $client = User::where('id', $clientId)
                        ->where('role', 'client')
                        ->first();
            
            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client non trouvé'
                ], 404);
            }

            // Vérifier que le client est bien lié à cet admin
            if (!$admin->linkedClients()->where('client_id', $clientId)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce client n\'est pas lié à votre compte'
                ], 403);
            }

            // CORRECTION : Utiliser update directement au lieu de la méthode suspend()
            $client->update([
                'is_suspended' => true,
                'suspended_until' => null,
            ]);

            // Déconnecter le client s'il est connecté
            $this->logoutClientSessions($clientId);

            return response()->json([
                'success' => true,
                'message' => 'Client suspendu avec succès!'
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur suspendClient:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suspension du client: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activer un client
     */
    public function activateClient($clientId)
    {
        try {
            $admin = Auth::user();
            
            // CORRECTION : Récupérer le client directement depuis User
            $client = User::where('id', $clientId)
                        ->where('role', 'client')
                        ->first();
            
            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client non trouvé'
                ], 404);
            }

            // Vérifier que le client est bien lié à cet admin
            if (!$admin->linkedClients()->where('client_id', $clientId)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce client n\'est pas lié à votre compte'
                ], 403);
            }

            // CORRECTION : Utiliser update directement au lieu de la méthode activate()
            $client->update([
                'is_suspended' => false,
                'suspended_until' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Client activé avec succès!'
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur activateClient:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'activation du client: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Déconnecter toutes les sessions d'un client
     */
    private function logoutClientSessions($clientId)
    {
        try {
            $client = User::find($clientId);
            if ($client) {
                // Ici vous pouvez implémenter la déconnexion des sessions
                // Pour l'instant, on se contente de logger
                \Log::info("Déconnexion forcée du client: {$client->name} (ID: {$clientId})");
                
                // Vous pouvez utiliser Laravel Sanctum ou autre système de sessions
                // pour forcer la déconnexion si nécessaire
            }
        } catch (\Exception $e) {
            \Log::error('Erreur logoutClientSessions:', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Rapports et statistiques (version AJAX pour le dashboard - CORRIGÉ)
     */
    public function reportsAjax(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $admin = Auth::user();
        
        // CORRECTION : Récupérer seulement les tables des clients liés
        $linkedClientTables = $admin->linkedClients()->pluck('table_number')->filter()->toArray();

        // ✅ TOUTES les commandes terminées (sans filtre de date) - CORRECTION
        $allCompletedOrders = Order::whereIn('table_number', $linkedClientTables)
            ->whereIn('status', ['terminé', 'livré'])
            ->with('items')
            ->get();

        // ✅ Commandes de la période pour les autres analyses - CORRECTION
        $completedOrdersForPeriod = Order::whereIn('table_number', $linkedClientTables)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['terminé', 'livré'])
            ->with('items')
            ->get();

        // Calcul des statistiques avec TOUTES les commandes
        $totalRevenue = $allCompletedOrders->sum('total');
        $totalOrders = $allCompletedOrders->count();
        $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        // Commandes par type (toutes aussi)
        $dineInOrders = $allCompletedOrders->where('order_type', 'sur_place')->count();
        $deliveryOrders = $allCompletedOrders->where('order_type', 'livraison')->count();

        // Performance du menu (sur la période pour garder l'analyse temporelle)
        $menuPerformance = $this->getMenuPerformance($completedOrdersForPeriod);
        $topItems = $this->getTopItems($menuPerformance);

        // Temps de préparation moyen (toutes commandes)
        $avgPreparationTime = $this->getAveragePreparationTime($allCompletedOrders);

        // ✅ CORRECTION: Statistiques détaillées avec TOUTES les données (sans filtre de date)
        $detailedStats = $this->getDetailedStatsAllTime($linkedClientTables);

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
     * Statistiques détaillées avec TOUTES les données (sans filtre de date)
     */
    private function getDetailedStatsAllTime($linkedClientTables = [])
    {
        // ✅ TOUTES les commandes (sans filtre de date) - CORRECTION
        $allOrders = Order::whereIn('table_number', $linkedClientTables)->get();
        $completedOrders = Order::whereIn('table_number', $linkedClientTables)
            ->whereIn('status', ['terminé', 'livré'])
            ->with('items')
            ->get();

        // Commandes par statut (toutes)
        $ordersByStatus = [
            'terminé' => $allOrders->where('status', 'terminé')->count(),
            'livré' => $allOrders->where('status', 'livré')->count(),
            'en_cours' => $allOrders->where('status', 'en_cours')->count(),
            'prêt' => $allOrders->where('status', 'prêt')->count(),
            'commandé' => $allOrders->where('status', 'commandé')->count(),
        ];

        // ✅ CORRECTION: Analyse des revenus avec TOUTES les données
        $revenueAnalysis = [
            'sur_place' => $completedOrders->where('order_type', 'sur_place')->sum('total'),
            'livraison' => $completedOrders->where('order_type', 'livraison')->sum('total'),
        ];

        // ✅ CORRECTION: Performance du menu avec TOUTES les données
        $menuItems = OrderItem::whereHas('order', function($query) use ($linkedClientTables) {
                $query->whereIn('table_number', $linkedClientTables)
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
     * Performance du menu (CORRIGÉ)
     */
    private function getMenuPerformance($orders)
    {
        $performance = [];

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $menuItemName = $item->menuItem->name ?? 'Article inconnu';
                $category = $item->menuItem->category ?? $item->category ?? 'repas';
                
                if (!isset($performance[$menuItemName])) {
                    $performance[$menuItemName] = [
                        'name' => $menuItemName,
                        'category' => $category,
                        'totalQuantity' => 0,
                        'totalRevenue' => 0,
                        'orders' => 0
                    ];
                }

                $performance[$menuItemName]['totalQuantity'] += $item->quantity;
                $performance[$menuItemName]['totalRevenue'] += $item->unit_price * $item->quantity;
                $performance[$menuItemName]['orders'] += 1;
            }
        }

        return $performance;
    }

    /**
     * Top articles (CORRIGÉ)
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
     * Statistiques détaillées (CORRIGÉ)
     */
    private function getDetailedStats($startDate, $endDate, $linkedClientTables = [])
    {
        $allOrders = Order::whereIn('table_number', $linkedClientTables)
                        ->whereBetween('created_at', [$startDate, $endDate])->get();
        $completedOrders = Order::whereIn('table_number', $linkedClientTables)
            ->whereBetween('created_at', [$startDate, $endDate])
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

        // Performance du menu - CORRECTION
        $menuItems = OrderItem::whereHas('order', function($query) use ($startDate, $endDate, $linkedClientTables) {
                $query->whereIn('table_number', $linkedClientTables)
                      ->whereBetween('created_at', [$startDate, $endDate])
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
     * Sauvegarder un rapport (CORRIGÉ)
     */
    public function saveReport(Request $request)
    {
        try {
            $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
            $endDate = $request->get('end_date', now()->format('Y-m-d'));
            $admin = Auth::user();
            
            // CORRECTION : Récupérer seulement les tables des clients liés
            $linkedClientTables = $admin->linkedClients()->pluck('table_number')->filter()->toArray();

            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
            
            // Récupérer les données du rapport
            $completedOrders = Order::whereIn('table_number', $linkedClientTables)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->whereIn('status', ['terminé', 'livré'])
                ->with('items')
                ->get();

            $totalRevenue = $completedOrders->sum('total');
            $totalOrders = $completedOrders->count();
            $menuPerformance = $this->getMenuPerformance($completedOrders);
            $topItems = $this->getTopItems($menuPerformance);

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
                'name' => 'Rapport ' . $type . ' du ' . $start->format('d/m/Y') . ' au ' . $end->format('d/m/Y'),
                'type' => $type,
                'start_date' => $start,
                'end_date' => $end,
                'data' => [
                    'total_revenue' => $totalRevenue,
                    'total_orders' => $totalOrders,
                    'top_items' => $topItems,
                    'period' => $start->format('d/m/Y') . ' - ' . $end->format('d/m/Y')
                ],
                'description' => 'Rapport généré automatiquement depuis le dashboard',
                'is_generated' => true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Rapport sauvegardé avec succès!',
                'report' => $report
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Erreur sauvegarde rapport:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde du rapport: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API pour les données de graphique (CORRIGÉ)
     */
    public function reportsChartData(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $admin = Auth::user();
        
        // CORRECTION : Récupérer seulement les tables des clients liés
        $linkedClientTables = $admin->linkedClients()->pluck('table_number')->filter()->toArray();

        $completedOrders = Order::whereIn('table_number', $linkedClientTables)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['terminé', 'livré'])
            ->with('items')
            ->get();

        $menuPerformance = $this->getMenuPerformance($completedOrders);
        $topItems = $this->getTopItems($menuPerformance);

        // Données par défaut si pas de données
        if (empty($topItems)) {
            $topItems = [
                ['name' => 'Pizza', 'category' => 'repas', 'totalRevenue' => 12000, 'totalQuantity' => 15, 'orders' => 12],
                ['name' => 'Burger', 'category' => 'repas', 'totalRevenue' => 9500, 'totalQuantity' => 12, 'orders' => 10],
                ['name' => 'Coca', 'category' => 'boisson', 'totalRevenue' => 4500, 'totalQuantity' => 20, 'orders' => 15],
                ['name' => 'Pâtes', 'category' => 'repas', 'totalRevenue' => 8000, 'totalQuantity' => 10, 'orders' => 8],
                ['name' => 'Eau', 'category' => 'boisson', 'totalRevenue' => 3000, 'totalQuantity' => 25, 'orders' => 18]
            ];
        }

        return response()->json([
            'topItems' => $topItems
        ]);
    }

    /**
     * Mettre à jour le statut d'une commande avec temps estimé
     */
    public function updateOrderStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:commandé,en_cours,prêt,livré,terminé',
            'estimated_time' => 'nullable|integer|min:1'
        ]);

        $order = Order::findOrFail($id);
        $order->status = $request->status;
        
        // Si l'admin fournit un temps estimé, l'utiliser
        if ($request->has('estimated_time') && $request->estimated_time) {
            $order->estimated_time = $request->estimated_time;
        }
        
        if ($request->status === 'prêt') {
            $order->marked_ready_at = now();
        }
        
        $order->save();

        return redirect()->back()->with('success', 'Statut de la commande mis à jour!');
    }

    /**
     * Mettre à jour le statut d'une commande via AJAX
     */
    public function updateOrderStatusAjax(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:commandé,en_cours,prêt,livré,terminé',
            'estimated_time' => 'nullable|integer|min:1'
        ]);

        try {
            $order = Order::findOrFail($id);
            $order->status = $request->status;
            
            // Si l'admin fournit un temps estimé, l'utiliser
            if ($request->has('estimated_time') && $request->estimated_time) {
                $order->estimated_time = $request->estimated_time;
            }
            
            if ($request->status === 'prêt') {
                $order->marked_ready_at = now();
            }
            
            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'Statut de la commande mis à jour!',
                'order' => $order
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
            ], 500);
        }
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
        $admin = Auth::user();
        
        // CORRECTION : Récupérer seulement les tables des clients liés
        $linkedClientTables = $admin->linkedClients()->pluck('table_number')->filter()->toArray();

        // Commandes terminées pour l'analyse
        $completedOrders = Order::whereIn('table_number', $linkedClientTables)
            ->whereBetween('created_at', [$startDate, $endDate])
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

        // Performance du menu - CORRECTION
        $menuPerformance = $this->getMenuPerformance($completedOrders);
        $topItems = $this->getTopItems($menuPerformance);

        // Temps de préparation moyen
        $avgPreparationTime = $this->getAveragePreparationTime($completedOrders);

        // Statistiques détaillées
        $detailedStats = $this->getDetailedStats($startDate, $endDate, $linkedClientTables);

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
@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <!-- En-tête -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold mb-2">Rapports et Analyses</h2>
            <p class="text-gray-600">
                Analyses basées sur {{ $totalOrders }} commandes terminées
            </p>
        </div>
        <form action="{{ route('admin.reports') }}" method="GET" class="flex gap-2">
            <input type="hidden" name="save_report" value="1">
            <input type="hidden" name="start_date" value="{{ $startDate }}">
            <input type="hidden" name="end_date" value="{{ $endDate }}">
            <button type="submit" 
                    class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors">
                💾 Sauvegarder ce rapport
            </button>
        </form>
    </div>

    <!-- Filtres par date -->
    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('admin.reports') }}" method="GET" class="flex gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date de début</label>
                <input type="date" name="start_date" value="{{ $startDate }}" 
                       class="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date de fin</label>
                <input type="date" name="end_date" value="{{ $endDate }}" 
                       class="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" 
                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                Appliquer
            </button>
        </form>
    </div>

    <!-- Métriques clés -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Carte Chiffre d'affaires -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <span class="text-blue-600 text-xl">💰</span>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Chiffre d'affaires</p>
                    <p class="text-2xl font-bold">{{ number_format($totalRevenue, 2, ',', ' ') }}€</p>
                </div>
            </div>
        </div>

        <!-- Carte Commandes totales -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-purple-100 rounded-lg">
                    <span class="text-purple-600 text-xl">📦</span>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Commandes totales</p>
                    <p class="text-2xl font-bold">{{ $totalOrders }}</p>
                </div>
            </div>
        </div>

        <!-- Carte Panier moyen -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-green-100 rounded-lg">
                    <span class="text-green-600 text-xl">📈</span>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Panier moyen</p>
                    <p class="text-2xl font-bold">{{ number_format($avgOrderValue, 2, ',', ' ') }}€</p>
                </div>
            </div>
        </div>

        <!-- Carte Temps moyen -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-gray-100 rounded-lg">
                    <span class="text-gray-600 text-xl">⏰</span>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Temps moyen</p>
                    <p class="text-2xl font-bold">{{ number_format($avgPreparationTime, 0) }}min</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Types de commande -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold flex items-center gap-2">
                    <span class="text-xl">📊</span>
                    Répartition des commandes
                </h3>
            </div>
            <div class="p-6 space-y-4">
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-blue-100 rounded-full">
                            <span class="text-blue-600">🍽️</span>
                        </div>
                        <div>
                            <p class="font-medium">Sur place</p>
                            <p class="text-sm text-gray-600">{{ $dineInOrders }} commandes</p>
                        </div>
                    </div>
                    <span class="px-3 py-1 bg-gray-200 text-gray-800 rounded-full text-sm font-semibold">
                        {{ $totalOrders > 0 ? round(($dineInOrders / $totalOrders) * 100) : 0 }}%
                    </span>
                </div>

                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-purple-100 rounded-full">
                            <span class="text-purple-600">🚚</span>
                        </div>
                        <div>
                            <p class="font-medium">Livraison</p>
                            <p class="text-sm text-gray-600">{{ $deliveryOrders }} commandes</p>
                        </div>
                    </div>
                    <span class="px-3 py-1 bg-gray-200 text-gray-800 rounded-full text-sm font-semibold">
                        {{ $totalOrders > 0 ? round(($deliveryOrders / $totalOrders) * 100) : 0 }}%
                    </span>
                </div>
            </div>
        </div>

        <!-- Top articles -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold flex items-center gap-2">
                    <span class="text-xl">📈</span>
                    Top 5 des articles
                </h3>
            </div>
            <div class="p-6 space-y-4">
                <!-- Graphique -->
                <div class="h-48" id="chart-container">
                    <div class="flex items-center justify-center h-full text-gray-500">
                        Chargement du graphique...
                    </div>
                </div>

                <!-- Légende -->
                <div class="flex items-center justify-center gap-4 text-sm">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-sm bg-blue-500"></div>
                        <span>Repas</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-sm bg-purple-500"></div>
                        <span>Boissons</span>
                    </div>
                </div>

                <!-- Liste -->
                <div class="space-y-3">
                    @foreach($topItems as $index => $item)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center text-sm font-bold">
                                {{ $index + 1 }}
                            </div>
                            <div>
                                <p class="font-medium">{{ $item['name'] }}</p>
                                <p class="text-sm text-gray-600">
                                    {{ $item['totalQuantity'] }} vendus • {{ $item['orders'] }} commandes
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold">{{ number_format($item['totalRevenue'], 2, ',', ' ') }}€</p>
                            <span class="px-2 py-1 border border-gray-300 rounded-full text-xs text-gray-600">
                                {{ $item['category'] }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques détaillées -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold">Statistiques détaillées</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="space-y-2">
                    <h4 class="font-semibold">Commandes par statut</h4>
                    <div class="space-y-1">
                        @foreach($detailedStats['ordersByStatus'] as $status => $count)
                        <div class="flex justify-between text-sm">
                            <span class="capitalize">{{ $status }}</span>
                            <span>{{ $count }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="space-y-2">
                    <h4 class="font-semibold">Analyse des revenus</h4>
                    <div class="space-y-1">
                        <div class="flex justify-between text-sm">
                            <span>Revenus sur place</span>
                            <span>{{ number_format($detailedStats['revenueAnalysis']['sur_place'], 2, ',', ' ') }}€</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span>Revenus livraison</span>
                            <span>{{ number_format($detailedStats['revenueAnalysis']['livraison'], 2, ',', ' ') }}€</span>
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <h4 class="font-semibold">Performance menu</h4>
                    <div class="space-y-1">
                        <div class="flex justify-between text-sm">
                            <span>Articles repas vendus</span>
                            <span>{{ $detailedStats['menuPerformance']['repas'] }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span>Boissons vendues</span>
                            <span>{{ $detailedStats['menuPerformance']['boisson'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Inclure Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Charger les données du graphique
    fetch('{{ route("admin.reports.chart") }}?start_date={{ $startDate }}&end_date={{ $endDate }}')
        .then(response => response.json())
        .then(data => {
            renderChart(data.topItems);
        })
        .catch(error => {
            console.error('Erreur lors du chargement des données:', error);
            renderChart(@json($topItems));
        });

    function renderChart(topItems) {
        const ctx = document.createElement('canvas');
        document.getElementById('chart-container').innerHTML = '';
        document.getElementById('chart-container').appendChild(ctx);

        const colors = topItems.map(item => 
            item.category === 'repas' ? 'rgb(59, 130, 246)' : 'rgb(147, 51, 234)'
        );

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: topItems.map(item => item.name),
                datasets: [{
                    label: 'Revenus (€)',
                    data: topItems.map(item => item.totalRevenue),
                    backgroundColor: colors,
                    borderColor: colors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    },
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const item = topItems[context.dataIndex];
                                return [
                                    `Revenus: ${item.totalRevenue.toFixed(2)}€`,
                                    `${item.totalQuantity} vendus • ${item.orders} commandes`
                                ];
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endsection
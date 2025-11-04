<div class="space-y-6" x-data="reportsComponent()" x-init="init()">
    <!-- En-tête -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold mb-2">Rapports et Analyses</h2>
            <p class="text-gray-600">
                Analyses basées sur {{ $totalOrders }} commandes terminées
            </p>
        </div>
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
                    <p class="text-2xl font-bold">{{ number_format($totalRevenue, 0, ',', ' ') }} FCFA</p>
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
                    <p class="text-2xl font-bold">{{ number_format($avgOrderValue, 0, ',', ' ') }} FCFA</p>
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
                    <p class="text-2xl font-bold">{{ number_format($avgPreparationTime, 0) }} min</p>
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

                <div class="relative" style="height: 250px;">
                    <canvas id="reportsChart"></canvas>
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
                    @forelse($topItems as $index => $item)
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
                            <p class="font-semibold">{{ number_format($item['totalRevenue'], 0, ',', ' ') }} FCFA</p>
                            <span class="px-2 py-1 border border-gray-300 rounded-full text-xs text-gray-600">
                                {{ $item['category'] }}
                            </span>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-4 text-gray-500">
                        Aucune donnée disponible pour la période sélectionnée
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques détaillées -->
<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold">Statistiques détaillées (Toutes périodes)</h3>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="space-y-2">
                <h4 class="font-semibold">Commandes par statut</h4>
                <div class="space-y-1">
                    @foreach($detailedStats['ordersByStatus'] as $status => $count)
                    <div class="flex justify-between text-sm">
                        <span class="capitalize">{{ str_replace('_', ' ', $status) }}</span>
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
                        <span>{{ number_format($detailedStats['revenueAnalysis']['sur_place'], 0, ',', ' ') }} FCFA</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span>Revenus livraison</span>
                        <span>{{ number_format($detailedStats['revenueAnalysis']['livraison'], 0, ',', ' ') }} FCFA</span>
                    </div>
                    <div class="flex justify-between text-sm font-semibold border-t pt-1">
                        <span>Total général</span>
                        <span>{{ number_format($detailedStats['revenueAnalysis']['sur_place'] + $detailedStats['revenueAnalysis']['livraison'], 0, ',', ' ') }} FCFA</span>
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
                    <div class="flex justify-between text-sm font-semibold border-t pt-1">
                        <span>Total articles</span>
                        <span>{{ $detailedStats['menuPerformance']['repas'] + $detailedStats['menuPerformance']['boisson'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ✅ DÉCLARER LA FONCTION GLOBALEMENT pour qu'Alpine.js puisse la trouver
window.reportsComponent = function() {
    return {
        filters: {
            start_date: '{{ $startDate }}',
            end_date: '{{ $endDate }}'
        },
        chart: null,
        
        init() {
            console.log('✅ Initialisation du composant rapports...');
            console.log('📊 Chart.js disponible:', typeof Chart !== 'undefined');
            console.log('📈 Données disponibles:', @json($topItems));
            
            // Attendre que le DOM soit complètement rendu
            this.$nextTick(() => {
                setTimeout(() => {
                    this.renderChart();
                }, 100);
            });
        },
        
        async applyFilters() {
    try {
        console.log('🔄 Application des filtres:', this.filters);
        
        const params = new URLSearchParams({
            start_date: this.filters.start_date,
            end_date: this.filters.end_date
        });

        // Recharger le contenu via AJAX
        const response = await fetch(`/admin/reports/ajax?${params}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        });

        if (response.ok) {
            const html = await response.text();
            console.log('✅ Données rechargées avec succès');
            
            // Remplacer tout le contenu du composant
            this.$el.outerHTML = html;
            
            // Réinitialiser Alpine.js sur le nouveau contenu
            Alpine.initTree(this.$el);
            
        } else {
            console.error('❌ Erreur HTTP:', response.status);
            alert('Erreur lors du chargement des données');
        }
        
    } catch (error) {
        console.error('❌ Erreur lors du filtrage:', error);
        alert('Erreur lors de l\'application des filtres');
    }
},
        
        async saveReport() {
            try {
                const response = await fetch('{{ route("admin.reports.save") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        start_date: this.filters.start_date,
                        end_date: this.filters.end_date
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ Rapport sauvegardé avec succès!');
                } else {
                    alert('❌ Erreur: ' + result.message);
                }
                
            } catch (error) {
                console.error('❌ Erreur lors de la sauvegarde:', error);
                alert('❌ Erreur réseau lors de la sauvegarde');
            }
        },
        
        renderChart() {
            console.log('🎨 Tentative de rendu du graphique...');
            const ctx = document.getElementById('reportsChart');
            
            if (!ctx) {
                console.error('❌ Canvas pour le graphique non trouvé');
                return;
            }
            
            const topItems = @json($topItems);
            console.log('📊 Données pour le graphique:', topItems);
            
            // Vérifier que Chart.js est disponible
            if (typeof Chart === 'undefined') {
                console.error('❌ Chart.js non disponible');
                this.showChartError(ctx, 'Chart.js non chargé');
                return;
            }
            
            // Si pas de données, afficher un message
            if (topItems.length === 0) {
                this.showChartError(ctx, 'Aucune donnée disponible pour le graphique');
                return;
            }
            
            // Détruire le graphique existant
            if (this.chart) {
                this.chart.destroy();
            }
            
            try {
                this.chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: topItems.map(item => item.name),
                        datasets: [{
                            label: 'Revenus (FCFA)',
                            data: topItems.map(item => item.totalRevenue),
                            backgroundColor: topItems.map(item => 
                                item.category === 'repas' ? 'rgba(59, 130, 246, 0.8)' : 'rgba(147, 51, 234, 0.8)'
                            ),
                            borderColor: topItems.map(item =>
                                item.category === 'repas' ? 'rgb(59, 130, 246)' : 'rgb(147, 51, 234)'
                            ),
                            borderWidth: 2,
                            borderRadius: 8,
                            borderSkipped: false,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    maxRotation: 45,
                                    minRotation: 45,
                                    font: {
                                        size: 11
                                    }
                                }
                            },
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.1)'
                                },
                                ticks: {
                                    callback: function(value) {
                                        return value.toLocaleString('fr-FR') + ' FCFA';
                                    },
                                    font: {
                                        size: 10
                                    }
                                }
                            }
                        },
                        plugins: {
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleFont: {
                                    size: 12
                                },
                                bodyFont: {
                                    size: 11
                                },
                                callbacks: {
                                    label: function(context) {
                                        const item = topItems[context.dataIndex];
                                        return [
                                            `Revenus: ${item.totalRevenue.toLocaleString('fr-FR')} FCFA`,
                                            `Quantité: ${item.totalQuantity} vendus`,
                                            `Commandes: ${item.orders}`
                                        ];
                                    }
                                }
                            },
                            legend: {
                                display: false
                            }
                        },
                        animation: {
                            duration: 1000,
                            easing: 'easeInOutQuart'
                        }
                    }
                });
                
                console.log('✅ Graphique créé avec succès!');
                
            } catch (error) {
                console.error('❌ Erreur lors de la création du graphique:', error);
                this.showChartError(ctx, 'Erreur: ' + error.message);
            }
        },
        
        showChartError(ctx, message) {
            ctx.parentElement.innerHTML = `
                <div class="text-center py-8 text-gray-500">
                    <div class="text-4xl mb-2">📊</div>
                    <p>${message}</p>
                    <p class="text-sm mt-2">Vérifiez la console pour plus de détails</p>
                </div>
            `;
        }
    }
}

// ✅ S'assurer qu'Alpine.js connaît le composant
document.addEventListener('alpine:init', () => {
    console.log('✅ Alpine.js initialisé - reportsComponent prêt');
    
    // Vérifier que Chart.js est disponible
    if (typeof Chart === 'undefined') {
        console.error('❌ Chart.js non disponible au moment de l\'initialisation Alpine');
    } else {
        console.log('✅ Chart.js disponible au moment de l\'initialisation Alpine');
    }
});

// ✅ Réinitialiser après chargement AJAX
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ DOM chargé - vérification des composants...');
});
</script>
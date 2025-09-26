@extends('layouts.admin')

@section('content')
<div x-data="dashboardComponent" x-ref="dashboard">
    <!-- Onglets -->
    <div class="mb-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <button @click="switchTab('overview')" 
                        :class="activeTab === 'overview' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                    Vue d'ensemble
                </button>
                <button @click="switchTab('orders')" 
                        :class="activeTab === 'orders' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                    Commandes
                </button>
                <button @click="switchTab('menu')" 
                        :class="activeTab === 'menu' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                    Menu
                </button>
                <button @click="switchTab('reports')" 
                        :class="activeTab === 'reports' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                    Rapports
                </button>
            </nav>
        </div>
    </div>

    <!-- Vue d'ensemble -->
    <div x-show="activeTab === 'overview'" class="space-y-6">
        <!-- Cartes de statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600 text-2xl">📊</div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Commandes Aujourd'hui</p>
                        <p class="text-2xl font-bold text-primary">{{ $stats['todayOrders'] }}</p>
                        <p class="text-xs text-gray-500">+2 depuis hier</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 text-2xl">⏳</div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Commandes en Attente</p>
                        <p class="text-2xl font-bold text-yellow-600">{{ $stats['pendingOrders'] }}</p>
                        <p class="text-xs text-gray-500">À traiter</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600 text-2xl">💰</div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Revenus Aujourd'hui</p>
                        <p class="text-2xl font-bold text-green-600">{{ number_format($stats['todayRevenue'], 0, ',', ' ') }} FCFA</p>
                        <p class="text-xs text-gray-500">+12% depuis hier</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600 text-2xl">🪑</div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Tables Actives</p>
                        <p class="text-2xl font-bold text-yellow-600">{{ $stats['activeTables'] }}/12</p>
                        <p class="text-xs text-gray-500">Tables occupées</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Commandes récentes -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-lg font-semibold">Commandes Récentes</h2>
                    <button @click="switchTab('orders')" class="text-blue-600 hover:text-blue-800 text-sm">
                        Voir tout →
                    </button>
                </div>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @foreach($recentOrders as $order)
                    <div class="flex items-center justify-between p-4 border rounded-lg">
                        <div>
                            <p class="font-semibold">#{{ $order->id }}</p>
                            <p class="text-sm text-gray-600">
                                Table {{ $order->table_number }} • {{ $order->items->count() }} articles
                            </p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="text-right">
                                <p class="font-semibold">{{ number_format($order->total, 0, ',', ' ') }} FCFA</p>
                                <p class="text-sm text-gray-600">
                                    {{ $order->created_at->format('H:i') }}
                                </p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold 
                                @if($order->status === 'commandé') bg-yellow-100 text-yellow-800
                                @elseif($order->status === 'en_cours') bg-blue-100 text-blue-800
                                @elseif($order->status === 'prêt') bg-green-100 text-green-800
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ ucfirst($order->status) }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Actions rapides -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <button @click="switchTab('orders')" 
               class="bg-orange-600 text-white rounded-lg p-6 text-center hover:bg-orange-700 transition-colors">
                <div class="space-y-2">
                    <div class="text-3xl">📋</div>
                    <div class="text-lg font-semibold">Gérer les Commandes</div>
                </div>
            </button>

            <button @click="switchTab('menu')" 
               class="bg-red-600 text-white rounded-lg p-6 text-center hover:bg-red-700 transition-colors">
                <div class="space-y-2">
                    <div class="text-3xl">🍽️</div>
                    <div class="text-lg font-semibold">Gérer le Menu</div>
                </div>
            </button>

            <button @click="switchTab('reports')" 
               class="bg-blue-600 text-white rounded-lg p-6 text-center hover:bg-blue-700 transition-colors">
                <div class="space-y-2">
                    <div class="text-3xl">📊</div>
                    <div class="text-lg font-semibold">Voir les Rapports</div>
                </div>
            </button>
        </div>
    </div>

    <!-- Onglet Commandes (chargement AJAX) -->
    <div x-show="activeTab === 'orders' && !loading && !error" id="orders-container">
        <div x-html="ordersContent"></div>
    </div>

    <!-- Onglet Menu (chargement AJAX) -->
    <div x-show="activeTab === 'menu' && !loading && !error" id="menu-container">
        <div x-html="menuContent"></div>
    </div>

    <!-- Indicateur de chargement -->
    <div x-show="loading" class="text-center py-12">
        <div class="text-6xl mb-4">🔄</div>
        <p class="text-lg text-gray-600">Chargement du contenu...</p>
    </div>

    <!-- Message d'erreur -->
    <div x-show="activeTab === 'orders' && error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
        <p class="font-semibold">Erreur lors du chargement des commandes.</p>
        <p class="mt-2">
            <a href="{{ route('admin.orders') }}" class="underline font-semibold">Accéder à la page complète</a>
        </p>
        <button @click="loadOrders()" class="mt-2 bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
            Réessayer
        </button>
    </div>

    <div x-show="activeTab === 'menu' && error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
        <p class="font-semibold">Erreur lors du chargement du menu.</p>
        <p class="mt-2">
            <a href="{{ route('admin.menu') }}" class="underline font-semibold">Accéder à la page complète</a>
        </p>
        <button @click="loadMenu()" class="mt-2 bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
            Réessayer
        </button>
    </div>

    <!-- Onglet Rapports -->
    <div x-show="activeTab === 'reports'">
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold">Rapports et Statistiques</h2>
            </div>
            <div class="p-6">
                <div class="text-center py-8">
                    <div class="text-6xl mb-4">📊</div>
                    <p class="text-lg text-gray-600 mb-4">Accédez aux rapports détaillés</p>
                    <a href="{{ route('admin.reports') }}" 
                       class="inline-flex items-center bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                        <span>📈 Voir les rapports complets</span>
                    </a>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="font-semibold mb-2">📋 Rapports disponibles</h3>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li>• Revenus par période</li>
                            <li>• Articles les plus populaires</li>
                            <li>• Performance des tables</li>
                            <li>• Temps de préparation moyen</li>
                        </ul>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="font-semibold mb-2">💡 Conseils</h3>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li>• Consultez les rapports quotidiennement</li>
                            <li>• Identifiez les tendances</li>
                            <li>• Optimisez votre menu</li>
                            <li>• Améliorez l'efficacité</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modaux globaux pour le menu -->
<div id="globalAddModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" style="display: none;">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4">
        <div class="p-6">
            <h3 class="text-lg font-semibold mb-4" id="globalModalTitle">Ajouter un nouvel article</h3>
            
            <form id="globalMenuForm" method="POST">
                @csrf
                <div id="globalMethodField"></div>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom de l'article</label>
                        <input type="text" name="name" id="globalItemName" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="ex: Burger Classique">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" id="globalItemDescription" required
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  placeholder="Décrivez l'article..."></textarea>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Prix (FCFA)</label>
                            <input type="number" name="price" id="globalItemPrice" required min="0"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="0">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Catégorie</label>
                            <select name="category" id="globalItemCategory" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="repas">🍽️ Repas</option>
                                <option value="boisson">🥤 Boisson</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <input type="checkbox" name="available" id="globalItemAvailable" value="1"
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" checked>
                        <label class="text-sm font-medium text-gray-700">Article disponible</label>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="globalCloseModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Annuler
                    </button>
                    <button type="submit" id="globalSubmitButton"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        <span id="globalSubmitText">Ajouter l'article</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Rendre le composant accessible globalement
window.dashboardComponent = null;

// Fonctions globales pour les modaux
function globalOpenAddModal() {
    // Réinitialiser le formulaire
    document.getElementById('globalMenuForm').reset();
    document.getElementById('globalModalTitle').textContent = 'Ajouter un nouvel article';
    document.getElementById('globalSubmitText').textContent = 'Ajouter l\'article';
    document.getElementById('globalMethodField').innerHTML = '';
    document.getElementById('globalMenuForm').action = '{{ route("admin.menu.add") }}';
    
    // Afficher le modal
    document.getElementById('globalAddModal').style.display = 'flex';
}

function globalCloseModal() {
    document.getElementById('globalAddModal').style.display = 'none';
}

// Fonctions globales pour les boutons du menu
function handleEditItem(item) {
    // Remplir le formulaire avec les données de l'article
    document.getElementById('globalItemName').value = item.name;
    document.getElementById('globalItemDescription').value = item.description;
    document.getElementById('globalItemPrice').value = item.price;
    document.getElementById('globalItemCategory').value = item.category;
    document.getElementById('globalItemAvailable').checked = item.available;
    
    // Changer le titre et l'action
    document.getElementById('globalModalTitle').textContent = 'Modifier l\'article';
    document.getElementById('globalSubmitText').textContent = 'Sauvegarder';
    document.getElementById('globalMethodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';
    document.getElementById('globalMenuForm').action = `{{ url('admin/menu') }}/${item.id}`;
    
    // Afficher le modal
    document.getElementById('globalAddModal').style.display = 'flex';
}

function handleAddPromotion(item) {
    // Implémentez la logique de promotion si nécessaire
    alert('Fonction promotion pour: ' + item.name);
}

function handleRemovePromotion(itemId) {
    if (confirm('Êtes-vous sûr de vouloir retirer la promotion ?')) {
        fetch(`{{ url('admin/menu') }}/${itemId}/promotion`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                alert('✅ Promotion retirée!');
                // Recharger le menu
                if (window.dashboardComponent) {
                    window.dashboardComponent.loadMenu();
                }
            } else {
                alert('❌ Erreur: ' + result.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('❌ Erreur réseau');
        });
    }
}

function handleToggleAvailability(itemId) {
    fetch(`{{ url('admin/menu') }}/${itemId}/toggle`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // Recharger le menu
            if (window.dashboardComponent) {
                window.dashboardComponent.loadMenu();
            }
        } else {
            alert('❌ Erreur: ' + result.message);
            // Recharger pour remettre le checkbox dans le bon état
            if (window.dashboardComponent) {
                window.dashboardComponent.loadMenu();
            }
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('❌ Erreur réseau');
    });
}

// Gestion de la soumission du formulaire global - VERSION CORRIGÉE
document.getElementById('globalMenuForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitButton = document.getElementById('globalSubmitButton');
    const originalText = submitButton.innerHTML;
    
    // Afficher un indicateur de chargement
    submitButton.innerHTML = '⏳ Enregistrement...';
    submitButton.disabled = true;
    
    try {
        console.log('Envoi des données vers:', this.action);
        
        const response = await fetch(this.action, {
            method: 'POST', // Toujours utiliser POST, Laravel gère PUT via _method
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        console.log('Réponse reçue:', response.status);
        
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }

        const result = await response.json();
        console.log('Résultat:', result);
        
        if (result.success) {
            // Succès
            globalCloseModal();
            
            // Afficher message de succès
            alert('✅ ' + result.message);
            
            // Recharger le contenu du menu après un délai
            setTimeout(() => {
                if (window.dashboardComponent) {
                    window.dashboardComponent.loadMenu();
                }
            }, 1000);
            
        } else {
            // Erreur
            alert('❌ ' + result.message);
        }
        
    } catch (error) {
        console.error('Erreur réseau:', error);
        alert('❌ Erreur réseau lors de la sauvegarde: ' + error.message);
    } finally {
        // Restaurer le bouton
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    }
});

// Fermer le modal global en cliquant à l'extérieur
document.getElementById('globalAddModal').addEventListener('click', function(e) {
    if (e.target === this) {
        globalCloseModal();
    }
});

// Fonction pour charger une catégorie de menu
function loadMenuCategory(category) {
    if (window.dashboardComponent) {
        window.dashboardComponent.loadMenu(category);
    }
}

// Fonction pour charger un statut de commande
function loadOrdersStatus(status) {
    if (window.dashboardComponent) {
        window.dashboardComponent.loadOrders(status);
    }
}

document.addEventListener('alpine:init', () => {
    Alpine.data('dashboardComponent', () => ({
        activeTab: 'overview',
        ordersContent: '',
        menuContent: '',
        loading: false,
        error: false,
        
        init() {
            // Stocker la référence globale
            window.dashboardComponent = this;
            
            console.log('Dashboard component initialized');
            
            // Écouter les événements de changement
            this.$watch('activeTab', (value) => {
                if (value === 'orders') {
                    this.loadOrders();
                } else if (value === 'menu') {
                    this.loadMenu();
                }
            });
        },
        
        async switchTab(tabName) {
            this.activeTab = tabName;
            this.loading = true;
            this.error = false;
            
            await this.$nextTick(); // Attendre le rendu
            
            if (tabName === 'orders') {
                await this.loadOrders();
            } else if (tabName === 'menu') {
                await this.loadMenu();
            }
            
            this.loading = false;
        },
        
        async loadOrders(status = 'pending') {
            try {
                this.loading = true;
                this.error = false;
                
                console.log('Chargement des commandes avec statut:', status);
                
                const response = await fetch(`{{ url('admin/orders/ajax') }}?status=${status}&_=${Date.now()}`);
                
                if (!response.ok) {
                    throw new Error(`Erreur HTTP: ${response.status}`);
                }
                
                const html = await response.text();
                this.ordersContent = html;
                
                console.log('Commandes chargées avec succès');
                
                // Réattacher les événements après le chargement du contenu
                setTimeout(() => {
                    this.attachOrderEvents();
                }, 100);
                
            } catch (error) {
                console.error('Erreur de chargement:', error);
                this.error = true;
                this.ordersContent = '<div class="text-center py-8 text-red-600">Erreur de chargement des commandes</div>';
            } finally {
                this.loading = false;
            }
        },
        
        async loadMenu(category = 'repas') {
            try {
                this.loading = true;
                this.error = false;
                
                console.log('Chargement du menu avec catégorie:', category);
                
                const response = await fetch(`{{ url('admin/menu/ajax') }}?category=${category}&_=${Date.now()}`);
                
                if (!response.ok) {
                    throw new Error(`Erreur HTTP: ${response.status}`);
                }
                
                const html = await response.text();
                this.menuContent = html;
                
                console.log('Menu chargé avec succès');
                
                // Réattacher les événements après le chargement du contenu
                setTimeout(() => {
                    this.attachMenuEvents();
                }, 100);
                
            } catch (error) {
                console.error('Erreur de chargement:', error);
                this.error = true;
                this.menuContent = '<div class="text-center py-8 text-red-600">Erreur de chargement du menu</div>';
            } finally {
                this.loading = false;
            }
        },
        
        attachOrderEvents() {
            // Attacher les événements aux boutons de statut des commandes
            const statusButtons = document.querySelectorAll('[data-status]');
            statusButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    const status = button.getAttribute('data-status');
                    this.loadOrders(status);
                });
            });
        },
        
        attachMenuEvents() {
            // Attacher les événements aux boutons de catégorie du menu
            const categoryButtons = document.querySelectorAll('[data-category]');
            categoryButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    const category = button.getAttribute('data-category');
                    this.loadMenu(category);
                });
            });
            
            // Attacher les événements aux boutons d'ajout
            const addButtons = document.querySelectorAll('[data-add-item]');
            addButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    globalOpenAddModal();
                });
            });
        }
    }));
});

// Gestion des modaux globaux
document.addEventListener('DOMContentLoaded', function() {
    // Fermer les modaux avec la touche Échap
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modals = document.querySelectorAll('[id$="Modal"]');
            modals.forEach(modal => {
                if (modal.style.display === 'flex') {
                    modal.style.display = 'none';
                }
            });
        }
    });
    
    console.log('Dashboard JavaScript chargé');
});
</script>

<style>
/* Animations pour les transitions */
[x-cloak] { display: none !important; }

.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from, .fade-leave-to {
    opacity: 0;
}
</style>
@endsection
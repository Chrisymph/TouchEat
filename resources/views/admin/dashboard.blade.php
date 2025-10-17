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

    <!-- Onglet Rapports (chargement AJAX) -->
    <div x-show="activeTab === 'reports' && !loading && !error" id="reports-container">
        <div x-html="reportsContent"></div>
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

    <div x-show="activeTab === 'reports' && error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
        <p class="font-semibold">Erreur lors du chargement des rapports.</p>
        <p class="mt-2">
            <a href="{{ route('admin.reports') }}" class="underline font-semibold">Accéder à la page complète</a>
        </p>
        <button @click="loadReports()" class="mt-2 bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
            Réessayer
        </button>
    </div>
</div>

<!-- Modal pour définir le temps de préparation -->
<div id="timeModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" style="display: none;">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
        <div class="p-6">
            <h3 class="text-lg font-semibold mb-4">Définir le temps de préparation</h3>
            
            <form id="timeForm" method="POST">
                @csrf
                <input type="hidden" name="status" value="en_cours">
                <input type="hidden" id="timeOrderId" name="order_id">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Temps de préparation estimé (minutes)
                        </label>
                        <input type="number" name="estimated_time" id="estimatedTime" 
                               required min="1" max="120" value="15"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="15">
                        <p class="text-sm text-gray-500 mt-1">Temps estimé pour préparer la commande</p>
                    </div>
                    
                    <div class="bg-blue-50 p-3 rounded-lg">
                        <p class="text-sm text-blue-700">
                            ⏱️ Ce temps sera affiché au client avec un compte à rebours.
                        </p>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeTimeModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                        Annuler
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                        Confirmer et démarrer le timer
                    </button>
                </div>
            </form>
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

<!-- Modal pour les promotions -->
<div id="promotionModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" style="display: none;">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
        <div class="p-6">
            <h3 class="text-lg font-semibold mb-4">Ajouter une promotion</h3>
            
            <form id="promotionForm" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Article</label>
                        <p class="text-lg font-semibold" id="promotionItemName">-</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prix actuel</label>
                        <p class="text-lg" id="promotionCurrentPrice">- FCFA</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pourcentage de réduction</label>
                        <input type="number" name="discount" id="promotionDiscount" required min="1" max="99"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="ex: 20">
                        <p class="text-sm text-gray-500 mt-1">Entre 1% et 99%</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nouveau prix</label>
                        <p class="text-lg font-semibold text-green-600" id="promotionNewPrice">- FCFA</p>
                    </div>
                    
                    <input type="hidden" name="original_price" id="promotionOriginalPrice">
                    <input type="hidden" name="item_id" id="promotionItemId">
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closePromotionModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Annuler
                    </button>
                    <button type="submit" id="promotionSubmitButton"
                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                        Appliquer la promotion
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal pour les détails de commande -->
<div id="orderDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" style="display: none;">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h3 class="text-xl font-semibold">Détails de la Commande #<span id="modalOrderId"></span></h3>
                <button type="button" onclick="closeOrderDetailsModal()" 
                        class="text-gray-400 hover:text-gray-600 text-2xl">
                    &times;
                </button>
            </div>
        </div>
        
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-200px)]">
            <!-- Informations de la commande -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="space-y-3">
                    <div>
                        <h4 class="font-semibold text-gray-700 mb-2">Informations Générales</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Table:</span>
                                <span class="font-semibold" id="modalTableNumber"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Type:</span>
                                <span class="font-semibold" id="modalOrderType"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Statut:</span>
                                <span class="font-semibold" id="modalOrderStatus"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Paiement:</span>
                                <span class="font-semibold" id="modalPaymentStatus"></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="space-y-3">
                    <div>
                        <h4 class="font-semibold text-gray-700 mb-2">Informations Client</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Téléphone:</span>
                                <span class="font-semibold" id="modalCustomerPhone"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Date:</span>
                                <span class="font-semibold" id="modalOrderDate"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Temps estimé:</span>
                                <span class="font-semibold" id="modalEstimatedTime"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Articles de la commande -->
            <div class="mb-6">
                <h4 class="font-semibold text-gray-700 mb-3">Articles Commandés</h4>
                <div class="space-y-3" id="modalOrderItems">
                    <!-- Les articles seront chargés ici dynamiquement -->
                </div>
            </div>

            <!-- Total -->
            <div class="border-t pt-4">
                <div class="flex justify-between items-center text-lg font-bold">
                    <span>Total de la commande:</span>
                    <span class="text-blue-600" id="modalOrderTotal"></span>
                </div>
            </div>
        </div>
        
        <div class="p-6 border-t border-gray-200 bg-gray-50">
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeOrderDetailsModal()" 
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                    Fermer
                </button>
                <!-- Le bouton "Voir la page complète" est caché car vous n'avez pas la vue -->
                <a href="#" id="modalFullDetailsLink" style="display: none;"
                   class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                    Voir la page complète
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Rendre le composant accessible globalement
window.dashboardComponent = null;

// Fonctions pour le modal de temps
function openTimeModal(orderId) {
    console.log('🟢 OUVERTURE du modal pour la commande:', orderId);
    
    // Mettre à jour l'ID de commande dans le formulaire
    document.getElementById('timeOrderId').value = orderId;
    
    // Afficher le modal
    document.getElementById('timeModal').style.display = 'flex';
    
    // Focus sur le champ de temps
    setTimeout(() => {
        const timeInput = document.getElementById('estimatedTime');
        timeInput.focus();
        timeInput.select();
    }, 100);
}

function closeTimeModal() {
    console.log('🔴 FERMETURE du modal');
    document.getElementById('timeModal').style.display = 'none';
}

// Fonctions pour le modal des détails de commande (VERSION CORRIGÉE)
function openOrderDetailsModal(orderId) {
    console.log('📋 Ouverture du modal pour la commande:', orderId);
    
    // Afficher le modal avec un indicateur de chargement
    document.getElementById('orderDetailsModal').style.display = 'flex';
    document.getElementById('modalOrderId').textContent = orderId;
    document.getElementById('modalOrderItems').innerHTML = `
        <div class="text-center py-4">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
            <p class="text-gray-600 mt-2">Chargement des détails...</p>
        </div>
    `;
    
    // URL CORRECTE - utilisez la route avec /ajax
    const apiUrl = `/admin/orders/${orderId}/ajax`;
    console.log('🔗 Appel de l\'API:', apiUrl);
    
    // Charger les détails de la commande via l'API JSON
    fetch(apiUrl)
        .then(response => {
            console.log('📥 Réponse reçue, statut:', response.status);
            if (!response.ok) {
                throw new Error('Erreur HTTP: ' + response.status + ' - URL: ' + apiUrl);
            }
            return response.json();
        })
        .then(data => {
            console.log('✅ Données reçues:', data);
            if (data.success) {
                populateOrderModalWithJSON(data.order, orderId);
            } else {
                throw new Error(data.message || 'Erreur inconnue du serveur');
            }
        })
        .catch(error => {
            console.error('❌ Erreur lors du chargement des détails:', error);
            document.getElementById('modalOrderItems').innerHTML = `
                <div class="text-center py-4 text-red-600">
                    ❌ ${error.message || 'Erreur lors du chargement des détails'}
                    <br><small>Vérifiez que la commande existe</small>
                </div>
            `;
        });
}

// Fonction pour remplir le modal avec les données JSON
function populateOrderModalWithJSON(orderData, orderId) {
    console.log('🎨 Remplissage du modal avec:', orderData);
    
    // Remplir les informations de base
    document.getElementById('modalOrderId').textContent = orderData.id || orderId;
    document.getElementById('modalTableNumber').textContent = orderData.table_number || 'N/A';
    document.getElementById('modalOrderType').textContent = orderData.order_type ? 
        orderData.order_type.charAt(0).toUpperCase() + orderData.order_type.slice(1) : 'Sur place';
    document.getElementById('modalOrderStatus').textContent = orderData.status ? 
        orderData.status.charAt(0).toUpperCase() + orderData.status.slice(1) : 'Commandé';
    document.getElementById('modalPaymentStatus').textContent = orderData.payment_status || 'Non payé';
    document.getElementById('modalCustomerPhone').textContent = orderData.customer_phone || 'Non renseigné';
    document.getElementById('modalOrderDate').textContent = orderData.created_at || 'N/A';
    document.getElementById('modalEstimatedTime').textContent = orderData.estimated_time || 'Non défini';
    document.getElementById('modalOrderTotal').textContent = orderData.total || '0 FCFA';
    
    // Remplir les articles
    const itemsContainer = document.getElementById('modalOrderItems');
    if (orderData.items && orderData.items.length > 0) {
        itemsContainer.innerHTML = orderData.items.map(item => `
            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                <div class="flex items-center space-x-3">
                    <span class="font-medium text-gray-800">${item.name}</span>
                    <span class="text-gray-600 text-sm bg-white px-2 py-1 rounded border">
                        x${item.quantity}
                    </span>
                </div>
                <div class="text-right">
                    <span class="font-semibold text-gray-800">
                        ${(item.total).toLocaleString('fr-FR')} FCFA
                    </span>
                    <p class="text-sm text-gray-500">
                        ${item.price.toLocaleString('fr-FR')} FCFA l'unité
                    </p>
                </div>
            </div>
        `).join('');
    } else {
        itemsContainer.innerHTML = `
            <div class="text-center py-4 text-gray-500">
                Aucun article trouvé dans cette commande
            </div>
        `;
    }
    
    // Cacher le lien "Voir la page complète" puisque vous n'avez pas la vue
    document.getElementById('modalFullDetailsLink').style.display = 'none';
    
    console.log('✅ Modal rempli avec succès');
}

// Fonction pour fermer le modal des détails
function closeOrderDetailsModal() {
    document.getElementById('orderDetailsModal').style.display = 'none';
}

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

// Fonctions pour les promotions
function openPromotionModal(item) {
    console.log('Opening promotion modal for:', item);
    document.getElementById('promotionItemName').textContent = item.name;
    document.getElementById('promotionCurrentPrice').textContent = item.price + ' FCFA';
    document.getElementById('promotionOriginalPrice').value = item.price;
    document.getElementById('promotionItemId').value = item.id;
    document.getElementById('promotionDiscount').value = '';
    document.getElementById('promotionNewPrice').textContent = '- FCFA';
    
    // Afficher le modal
    document.getElementById('promotionModal').style.display = 'flex';
}

function closePromotionModal() {
    document.getElementById('promotionModal').style.display = 'none';
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

function handleAddPromotion(itemId) {
    console.log('🎯 handleAddPromotion appelée avec ID:', itemId);
    
    // Méthode simple et directe - récupérer via une requête API
    fetch(`/admin/menu/${itemId}/ajax`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur HTTP: ' + response.status);
            }
            return response.json();
        })
        .then(item => {
            console.log('✅ Données récupérées:', item);
            
            // Vérifier que nous avons bien les données
            if (!item || !item.name || !item.price) {
                throw new Error('Données incomplètes reçues');
            }
            
            openPromotionModal(item);
        })
        .catch(error => {
            console.error('❌ Erreur lors de la récupération:', error);
            
            // Fallback: essayer de récupérer depuis le DOM
            console.log('🔄 Tentative de récupération depuis le DOM...');
            
            const buttons = document.querySelectorAll('button');
            let targetButton = null;
            
            for (let button of buttons) {
                const onclickAttr = button.getAttribute('onclick');
                if (onclickAttr && onclickAttr.includes(`handleAddPromotion(${itemId})`)) {
                    targetButton = button;
                    break;
                }
            }
            
            if (targetButton) {
                const card = targetButton.closest('.bg-white');
                if (card) {
                    const nameElement = card.querySelector('h3');
                    const priceElement = card.querySelector('.text-lg.font-bold');
                    
                    if (nameElement && priceElement) {
                        const name = nameElement.textContent.trim();
                        const priceText = priceElement.textContent.trim();
                        const price = parseFloat(priceText.replace(/[^\d,]/g, '').replace(',', ''));
                        
                        if (!isNaN(price)) {
                            const item = { id: itemId, name: name, price: price };
                            console.log('✅ Données récupérées depuis DOM:', item);
                            openPromotionModal(item);
                            return;
                        }
                    }
                }
            }
            
            alert('❌ Erreur: Impossible de charger les données de l\'article (ID: ' + itemId + '). Veuillez réessayer.');
        });
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

// Fonction pour supprimer un article
function handleDeleteItem(itemId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet article ? Cette action est irréversible.')) {
        console.log('🗑️ Suppression de l\'article ID:', itemId);
        
        fetch(`{{ url('admin/menu') }}/${itemId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur HTTP: ' + response.status);
            }
            return response.json();
        })
        .then(result => {
            if (result.success) {
                console.log('✅ Suppression réussie:', result.message);
                
                // Afficher un message de succès
                alert('✅ ' + result.message);
                
                // Recharger le contenu du menu sans quitter l'onglet
                if (window.dashboardComponent) {
                    window.dashboardComponent.loadMenu();
                }
            } else {
                throw new Error(result.message);
            }
        })
        .catch(error => {
            console.error('❌ Erreur lors de la suppression:', error);
            alert('❌ Erreur lors de la suppression: ' + error.message);
        });
    }
}

// Gestion de la soumission du formulaire de temps
document.getElementById('timeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    console.log('📤 Soumission du formulaire de temps');
    
    const formData = new FormData(this);
    const orderId = document.getElementById('timeOrderId').value;
    const submitButton = this.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    
    // Afficher l'indicateur de chargement
    submitButton.innerHTML = '⏳ Confirmation...';
    submitButton.disabled = true;
    
    console.log(`📦 Données: orderId=${orderId}`);
    
    // Utiliser l'endpoint AJAX pour la mise à jour
    fetch(`/admin/orders/${orderId}/status-ajax`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        console.log('📥 Réponse reçue, statut:', response.status);
        if (!response.ok) {
            throw new Error('Erreur HTTP: ' + response.status);
        }
        return response.json();
    })
    .then(result => {
        console.log('✅ Résultat:', result);
        
        if (result.success) {
            // Fermer le modal
            closeTimeModal();
            
            // Afficher un message de succès
            alert('✅ ' + result.message);
            
            // Recharger les commandes
            if (window.dashboardComponent) {
                window.dashboardComponent.loadOrders();
            }
        } else {
            throw new Error(result.message || 'Erreur inconnue');
        }
    })
    .catch(error => {
        console.error('❌ Erreur:', error);
        alert('❌ Erreur: ' + error.message);
    })
    .finally(() => {
        // Restaurer le bouton
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    });
});

// Gestion de la soumission du formulaire global
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
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                         document.querySelector('input[name="_token"]')?.value;
        
        if (!csrfToken) {
            throw new Error('Token CSRF non trouvé');
        }
        
        const response = await fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
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

// Calcul du nouveau prix en temps réel pour les promotions
document.getElementById('promotionDiscount').addEventListener('input', function(e) {
    const discount = parseInt(e.target.value) || 0;
    const originalPrice = parseFloat(document.getElementById('promotionOriginalPrice').value);
    
    if (discount > 0 && discount <= 99) {
        const newPrice = originalPrice * (1 - discount / 100);
        document.getElementById('promotionNewPrice').textContent = newPrice.toFixed(0) + ' FCFA';
    } else {
        document.getElementById('promotionNewPrice').textContent = '- FCFA';
    }
});

// Gestion de la soumission du formulaire de promotion
document.getElementById('promotionForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitButton = document.getElementById('promotionSubmitButton');
    const originalText = submitButton.innerHTML;
    
    // Afficher un indicateur de chargement
    submitButton.innerHTML = '⏳ Application...';
    submitButton.disabled = true;
    
    try {
        const itemId = document.getElementById('promotionItemId').value;
        const response = await fetch(`{{ url('admin/menu') }}/${itemId}/promotion`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const result = await response.json();
        
        if (result.success) {
            closePromotionModal();
            alert('✅ ' + result.message);
            
            // Recharger le menu
            if (window.dashboardComponent) {
                window.dashboardComponent.loadMenu();
            }
        } else {
            alert('❌ ' + result.message);
        }
        
    } catch (error) {
        console.error('Erreur:', error);
        alert('❌ Erreur réseau lors de l\'application de la promotion');
    } finally {
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    }
});

// Fermer les modaux en cliquant à l'extérieur
document.getElementById('timeModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeTimeModal();
    }
});

document.getElementById('globalAddModal').addEventListener('click', function(e) {
    if (e.target === this) {
        globalCloseModal();
    }
});

document.getElementById('promotionModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePromotionModal();
    }
});

document.getElementById('orderDetailsModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeOrderDetailsModal();
    }
});

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

// Gestion des événements pour les boutons "Voir Détails" - DÉLÉGATION D'ÉVÉNEMENTS
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('view-order-details-btn')) {
        const orderId = e.target.getAttribute('data-order-id');
        console.log('📋 Clic sur Voir Détails pour la commande:', orderId);
        openOrderDetailsModal(orderId);
    }
});

// Gestion des événements pour les boutons "Accepter" - DÉLÉGATION D'ÉVÉNEMENTS
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('accept-order-btn')) {
        const orderId = e.target.getAttribute('data-order-id');
        console.log('🟡 Clic sur Accepter pour la commande:', orderId);
        openTimeModal(orderId);
    }
});

document.addEventListener('alpine:init', () => {
    Alpine.data('dashboardComponent', () => ({
        activeTab: 'overview',
        ordersContent: '',
        menuContent: '',
        reportsContent: '',
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
                } else if (value === 'reports') {
                    this.loadReports();
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
            } else if (tabName === 'reports') {
                await this.loadReports();
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
        
        async loadReports(startDate = null, endDate = null) {
            try {
                this.loading = true;
                this.error = false;
                
                let url = `{{ url('admin/reports/ajax') }}?_=${Date.now()}`;
                if (startDate && endDate) {
                    url += `&start_date=${startDate}&end_date=${endDate}`;
                }
                
                console.log('Chargement des rapports:', url);
                
                const response = await fetch(url);
                
                if (!response.ok) {
                    throw new Error(`Erreur HTTP: ${response.status}`);
                }
                
                const html = await response.text();
                this.reportsContent = html;
                
                console.log('Rapports chargés avec succès');
                
                // Réinitialiser Chart.js après chargement
                setTimeout(() => {
                    const reportsComponent = document.querySelector('[x-data="reportsComponent()"]');
                    if (reportsComponent && reportsComponent.__x) {
                        reportsComponent.__x.$data.renderChart();
                    }
                }, 100);
                
            } catch (error) {
                console.error('Erreur de chargement des rapports:', error);
                this.error = true;
                this.reportsContent = '<div class="text-center py-8 text-red-600">Erreur de chargement des rapports</div>';
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
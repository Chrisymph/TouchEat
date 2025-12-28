@extends('layouts.admin')

@section('content')
<div x-data="dashboardComponent()" x-init="init()" x-ref="dashboard">
    <!-- Onglets -->
    <div class="mb-6 flex justify-center">
        <div class="bg-gray-200 rounded-lg shadow-sm inline-flex py-1 px-1">
            <nav class="flex space-x-2">
                <button @click="switchTab('overview')" 
                        :class="activeTab === 'overview' ? 'bg-orange-100 text-orange-600 font-semibold ring-2 ring-orange-400' : 'bg-gray-100 text-gray-600 hover:bg-orange-200'"
                        class="whitespace-nowrap py-2 px-4 rounded-md transition-all duration-200">
                    Vue d'ensemble
                </button>
                <button @click="switchTab('orders')" 
                        :class="activeTab === 'orders' ? 'bg-orange-100 text-orange-600 font-semibold ring-2 ring-orange-400' : 'bg-gray-100 text-gray-600 hover:bg-orange-200'"
                        class="whitespace-nowrap py-2 px-4 rounded-md transition-all duration-200">
                    Commandes
                </button>
                <button @click="switchTab('menu')" 
                        :class="activeTab === 'menu' ? 'bg-orange-100 text-orange-600 font-semibold ring-2 ring-orange-400' : 'bg-gray-100 text-gray-600 hover:bg-orange-200'"
                        class="whitespace-nowrap py-2 px-4 rounded-md transition-all duration-200">
                    Menu
                </button>
                <button @click="switchTab('clients')" 
                        :class="activeTab === 'clients' ? 'bg-orange-100 text-orange-600 font-semibold ring-2 ring-orange-400' : 'bg-gray-100 text-gray-600 hover:bg-orange-200'"
                        class="whitespace-nowrap py-2 px-4 rounded-md transition-all duration-200">
                    Clients
                </button>
                <button @click="switchTab('reports')" 
                        :class="activeTab === 'reports' ? 'bg-orange-100 text-orange-600 font-semibold ring-2 ring-orange-400' : 'bg-gray-100 text-gray-600 hover:bg-orange-200'"
                        class="whitespace-nowrap py-2 px-4 rounded-md transition-all duration-200">
                    Rapports
                </button>
            </nav>
        </div>
    </div>

    <!-- Vue d'ensemble -->
    <div x-show="activeTab === 'overview'" class="space-y-6">
        <!-- Cartes de statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-bold text-black-600">Commandes Aujourd'hui</p>
                    <div class="text-2xl rounded-full">📊</div>
                </div>
                <div class="mt-2">
                    <p class="text-2xl font-bold text-orange-600">{{ $stats['todayOrders'] }}</p>
                    <p class="text-xs text-gray-500">+2 depuis hier</p>
                </div>                
            </div>

            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-bold text-black-600">Commandes en Attente</p>
                    <div class="rounded-full text-2xl">⏳</div>
                </div>
                <div class="mt-2">
                    <p class="text-2xl font-bold text-yellow-500">{{ $stats['pendingOrders'] }}</p>
                    <p class="text-xs text-gray-500">À traiter</p>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-bold text-black-600">Revenus Aujourd'hui</p>
                    <div class="text-2xl">💰</div>
                </div>  
                <div class="mt-2">                        
                    <p class="text-2xl font-bold text-green-600">{{ number_format($stats['todayRevenue'], 0, ',', ' ') }} FCFA</p>
                    <p class="text-xs text-gray-500">+12% depuis hier</p>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-bold text-black-600">Tables Actives</p>
                    <div class="text-2xl">🪑</div>
                </div>
                <div class="mt-2">                 
                    <p class="text-2xl font-bold text-yellow-600">{{ $stats['activeTables'] }}/12</p>
                    <p class="text-xs text-gray-500">Tables occupées</p>
                </div>
            </div>
        </div>

        <!-- Commandes récentes -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-lg font-semibold">Commandes Récentes</h2>
                    <button @click="switchTab('orders')" class="bg-gray-200 font-bold text-black hover:bg-blue-600 
                        hover:text-white text-sm px-3 py-2 rounded transition-colors">
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
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
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

            <button @click="switchTab('clients')" 
               class="bg-purple-600 text-white rounded-lg p-6 text-center hover:bg-purple-700 transition-colors">
                <div class="space-y-2">
                    <div class="text-3xl">👥</div>
                    <div class="text-lg font-semibold">Gérer les Clients</div>
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
    <div x-show="activeTab === 'orders'" id="orders-container">
        <template x-if="!loading && !error">
            <div x-html="ordersContent"></div>
        </template>
        
        <!-- Indicateur de chargement spécifique -->
        <div x-show="loading" class="text-center py-12">
            <div class="text-6xl mb-4">🔄</div>
            <p class="text-lg text-gray-600">Chargement des commandes...</p>
        </div>
        
        <!-- Message d'erreur spécifique -->
        <div x-show="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <p class="font-semibold">Erreur lors du chargement des commandes.</p>
            <button @click="loadOrders()" class="mt-2 bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                Réessayer
            </button>
        </div>
    </div>

    <!-- Onglet Menu (chargement AJAX) -->
    <div x-show="activeTab === 'menu'" id="menu-container">
        <template x-if="!loading && !error">
            <div x-html="menuContent"></div>
        </template>
        
        <!-- Indicateur de chargement spécifique -->
        <div x-show="loading" class="text-center py-12">
            <div class="text-6xl mb-4">🔄</div>
            <p class="text-lg text-gray-600">Chargement du menu...</p>
        </div>
        
        <!-- Message d'erreur spécifique -->
        <div x-show="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <p class="font-semibold">Erreur lors du chargement du menu.</p>
            <button @click="loadMenu()" class="mt-2 bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                Réessayer
            </button>
        </div>
    </div>

    <!-- Onglet Clients (chargement AJAX) -->
    <div x-show="activeTab === 'clients'" id="clients-container">
        <template x-if="!loading && !error">
            <div x-html="clientsContent"></div>
        </template>
        
        <!-- Indicateur de chargement spécifique -->
        <div x-show="loading" class="text-center py-12">
            <div class="text-6xl mb-4">🔄</div>
            <p class="text-lg text-gray-600">Chargement des clients...</p>
        </div>
        
        <!-- Message d'erreur spécifique -->
        <div x-show="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <p class="font-semibold">Erreur lors du chargement des clients.</p>
            <button @click="loadClients()" class="mt-2 bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                Réessayer
            </button>
        </div>
    </div>

    <!-- Onglet Rapports (chargement AJAX) -->
    <div x-show="activeTab === 'reports'" id="reports-container">
        <template x-if="!loading && !error">
            <div x-html="reportsContent"></div>
        </template>
        
        <!-- Indicateur de chargement spécifique -->
        <div x-show="loading" class="text-center py-12">
            <div class="text-6xl mb-4">🔄</div>
            <p class="text-lg text-gray-600">Chargement des rapports...</p>
        </div>
        
        <!-- Message d'erreur spécifique -->
        <div x-show="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <p class="font-semibold">Erreur lors du chargement des rapports.</p>
            <button @click="loadReports()" class="mt-2 bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                Réessayer
            </button>
        </div>
    </div>

    <!-- Indicateur de chargement global (en attendant la navigation) -->
    <div x-show="loading && activeTab !== 'overview'" class="text-center py-12">
        <div class="text-6xl mb-4">🔄</div>
        <p class="text-lg text-gray-600">Chargement du contenu...</p>
    </div>
</div>

<!-- Modal pour définir le temps de préparation -->
<div id="timeModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" style="display: none;">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
        <div class="p-6">
            <h3 class="text-lg font-semibold mb-4">⏱️ Définir le temps de préparation</h3>
            
            <form id="timeForm" method="POST">
                @csrf
                <input type="hidden" name="status" value="en_cours">
                <input type="hidden" id="timeOrderId" name="order_id">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Temps de préparation estimé (minutes)
                        </label>
                        <input type="number" name="preparation_time" id="estimatedTime" 
                               required min="1" max="120" value="15"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="15">
                        <p class="text-sm text-gray-500 mt-1">Temps estimé pour préparer la commande</p>
                    </div>
                    
                    <!-- Suggestions de temps -->
                    <div class="grid grid-cols-4 gap-2">
                        <button type="button" onclick="document.getElementById('estimatedTime').value = '5'" 
                                class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-sm transition-colors">
                            5 min
                        </button>
                        <button type="button" onclick="document.getElementById('estimatedTime').value = '10'" 
                                class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-sm transition-colors">
                            10 min
                        </button>
                        <button type="button" onclick="document.getElementById('estimatedTime').value = '15'" 
                                class="px-3 py-2 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded text-sm transition-colors">
                            15 min
                        </button>
                        <button type="button" onclick="document.getElementById('estimatedTime').value = '20'" 
                                class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-sm transition-colors">
                            20 min
                        </button>
                    </div>
                    
                    <div class="bg-blue-50 p-3 rounded-lg">
                        <p class="text-sm text-blue-700">
                            ⏱️ Un compte à rebours sera affiché au client.
                        </p>
                        <p class="text-xs text-blue-600 mt-1">
                            ✅ Notification visuelle à 5 minutes de la fin<br>
                            🔴 Animation rouge quand le temps est écoulé
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
                        Démarrer le timer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal pour ajouter du temps -->
<div id="addTimeModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50" style="display: none;">
    <div class="bg-white rounded-2xl p-6 max-w-md w-full shadow-xl">
        <h3 class="text-2xl font-bold text-gray-800 mb-2">Ajouter du temps</h3>
        <p class="text-gray-600 mb-4">Commande #<span id="modalOrderIdTime"></span></p>
        
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Temps supplémentaire (minutes)
                </label>
                <select id="additionalTime" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="5">+5 minutes</option>
                    <option value="10">+10 minutes</option>
                    <option value="15">+15 minutes</option>
                    <option value="20">+20 minutes</option>
                    <option value="30">+30 minutes</option>
                </select>
            </div>
            
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-700">Temps actuel estimé:</span>
                    <span class="font-semibold" id="currentTimeDisplay">0 min</span>
                </div>
                <div class="flex justify-between items-center text-sm mt-2">
                    <span class="text-gray-700">Nouveau temps estimé:</span>
                    <span class="font-semibold text-blue-600" id="newTimeDisplay">0 min</span>
                </div>
            </div>
        </div>
        
        <div class="flex gap-3 mt-6">
            <button type="button" id="cancelAddTime" 
                    class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-3 rounded-lg font-semibold transition-all duration-300">
                Annuler
            </button>
            <button type="button" id="confirmAddTime" 
                    class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-3 rounded-lg font-semibold transition-all duration-300">
                Ajouter le temps
            </button>
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
                <!-- Bouton Imprimer Reçu ajouté -->
                <button type="button" id="modalPrintReceiptBtn"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors print-receipt-btn">
                    🖨️ Imprimer Reçu
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour ajouter des clients -->
<div id="addClientModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" style="display: none;">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-4xl mx-4 max-h-[90vh] overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h3 class="text-xl font-semibold">Ajouter des Clients</h3>
                <button type="button" onclick="closeAddClientModal()" 
                        class="text-gray-400 hover:text-gray-600 text-2xl">
                    &times;
                </button>
            </div>
        </div>
        
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-200px)]">
            <!-- Liste des clients disponibles -->
            <div class="space-y-4" id="availableClientsList">
                <!-- Le contenu sera chargé dynamiquement -->
            </div>
        </div>
        
        <div class="p-6 border-t border-gray-200 bg-gray-50">
            <div class="flex justify-between items-center">
                <span class="text-gray-600" id="selectedCount">0 client(s) sélectionné(s)</span>
                <div class="flex space-x-3">
                    <button type="button" onclick="closeAddClientModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors duration-300">
                        Annuler
                    </button>
                    <button type="button" onclick="linkSelectedClients()" 
                            id="linkClientsButton"
                            class="px-4 py-2 bg-blue-400 text-white rounded-lg font-semibold transition-all duration-300 cursor-not-allowed">
                        Lier 0 client(s)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour le rapport par date AVEC BOUTON TÉLÉCHARGER -->
<div id="dateReportModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" style="display: none;">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-6xl mx-4 max-h-[90vh] overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h3 class="text-xl font-semibold" id="modalReportTitle">Rapport détaillé</h3>
                <button type="button" onclick="closeDateReportModal()" 
                        class="text-gray-400 hover:text-gray-600 text-2xl">
                    &times;
                </button>
            </div>
        </div>
        
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-200px)]">
            <div id="dateReportContent">
                <!-- Le contenu du rapport sera chargé ici -->
            </div>
        </div>
        
        <div class="p-6 border-t border-gray-200 bg-gray-50">
            <div class="flex justify-between items-center">
                <button type="button" onclick="downloadDateReport()" 
                        id="downloadReportBtn"
                        class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold transition-all duration-300 flex items-center gap-2">
                    <span>📥 Télécharger Rapport PDF</span>
                </button>
                <button type="button" onclick="closeDateReportModal()" 
                        class="px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white rounded-lg font-semibold transition-all duration-300">
                    Fermer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Rendre le composant accessible globalement
window.dashboardComponent = null;

// ============================================================================
// FONCTIONS POUR L'IMPRESSION DES REÇUS
// ============================================================================

// Fonction pour imprimer le reçu
async function printReceipt(orderId) {
    try {
        console.log('🖨️ Impression du reçu pour la commande:', orderId);
        
        // Vérifier d'abord si la commande peut être imprimée
        const checkResponse = await fetch(`/admin/orders/${orderId}/receipt`);
        const checkResult = await checkResponse.json();
        
        if (!checkResult.success) {
            throw new Error(checkResult.message);
        }
        
        // Ouvrir dans une nouvelle fenêtre pour impression
        const printUrl = `/admin/orders/${orderId}/print?auto_print=1&t=${Date.now()}`;
        const printWindow = window.open(printUrl, `receipt_${orderId}`, 'width=400,height=600,scrollbars=no,toolbar=no');
        
        if (!printWindow) {
            throw new Error('Veuillez autoriser les pop-ups pour l\'impression du reçu');
        }
        
        showToast('🖨️ Ouverture de l\'impression...', 'success');
        
        // Vérifier si la fenêtre s'est ouverte correctement
        let checkCount = 0;
        const checkInterval = setInterval(() => {
            if (printWindow.closed) {
                clearInterval(checkInterval);
                return;
            }
            
            if (checkCount > 10) { // Timeout après 5 secondes
                clearInterval(checkInterval);
                showToast('✅ Reçu prêt pour impression', 'success');
            }
            
            checkCount++;
        }, 500);
        
    } catch (error) {
        console.error('❌ Erreur impression reçu:', error);
        showToast('❌ Erreur lors de l\'impression: ' + error.message, 'error');
    }
}

// Fonction pour prévisualiser le reçu
async function previewReceipt(orderId) {
    try {
        const printUrl = `/admin/orders/${orderId}/print?t=${Date.now()}`;
        const previewWindow = window.open(printUrl, `preview_${orderId}`, 'width=500,height=700,scrollbars=yes,toolbar=yes');
        
        if (!previewWindow) {
            throw new Error('Veuillez autoriser les pop-ups pour la prévisualisation');
        }
        
    } catch (error) {
        console.error('Erreur prévisualisation reçu:', error);
        showToast('❌ Erreur lors de la prévisualisation: ' + error.message, 'error');
    }
}

// Fonctions pour le modal de temps de préparation
function openTimeModal(orderId) {
    document.getElementById('timeOrderId').value = orderId;
    document.getElementById('timeModal').style.display = 'flex';
    
    setTimeout(() => {
        const timeInput = document.getElementById('estimatedTime');
        timeInput.focus();
        timeInput.select();
    }, 100);
}

function closeTimeModal() {
    document.getElementById('timeModal').style.display = 'none';
}

// Fonctions pour le modal d'ajout de temps
function openAddTimeModal(orderId, currentTime) {
    console.log('⏱️ Ouverture modal ajout temps pour commande:', orderId, 'Temps actuel:', currentTime);
    
    document.getElementById('addTimeModal').style.display = 'flex';
    document.getElementById('modalOrderIdTime').textContent = orderId;
    document.getElementById('currentTimeDisplay').textContent = currentTime + ' min';
    
    // Stocker l'ID de commande
    document.getElementById('addTimeModal').setAttribute('data-order-id', orderId);
    document.getElementById('addTimeModal').setAttribute('data-current-time', currentTime);
    
    // Calculer le nouveau temps
    updateNewTimeDisplay();
}

function closeAddTimeModal() {
    document.getElementById('addTimeModal').style.display = 'none';
}

function updateNewTimeDisplay() {
    const currentTime = parseInt(document.getElementById('addTimeModal').getAttribute('data-current-time'));
    const additionalTime = parseInt(document.getElementById('additionalTime').value);
    const newTime = currentTime + additionalTime;
    
    document.getElementById('newTimeDisplay').textContent = newTime + ' min';
}

async function addTimeToOrder(orderId, additionalTime) {
    try {
        console.log('⏱️ Ajout de temps pour commande:', orderId, 'Temps supplémentaire:', additionalTime);
        
        const response = await fetch(`/admin/orders/${orderId}/add-time`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                additional_time: additionalTime
            })
        });

        const data = await response.json();

        if (data.success) {
            showToast('⏱️ Temps supplémentaire ajouté avec succès!', 'success');
            closeAddTimeModal();
            
            // Recharger les commandes
            if (window.dashboardComponent) {
                const currentStatus = localStorage.getItem('adminOrdersStatus') || 'pending';
                window.dashboardComponent.loadOrders(currentStatus);
            }
        } else {
            showToast('❌ ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Erreur lors de l\'ajout de temps:', error);
        showToast('❌ Erreur lors de l\'ajout de temps', 'error');
    }
}

// Gestion de la soumission du formulaire de temps de préparation
document.getElementById('timeForm')?.addEventListener('submit', function(e) {
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
    
    // Utiliser l'endpoint pour définir le temps de préparation
    fetch(`/admin/orders/${orderId}/set-preparation-time`, {
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
            showToast('✅ ' + result.message, 'success');
            
            // Recharger les commandes
            if (window.dashboardComponent) {
                const currentStatus = localStorage.getItem('adminOrdersStatus') || 'pending';
                window.dashboardComponent.loadOrders(currentStatus);
            }
        } else {
            throw new Error(result.message || 'Erreur inconnue');
        }
    })
    .catch(error => {
        console.error('❌ Erreur:', error);
        showToast('❌ Erreur: ' + error.message, 'error');
    })
    .finally(() => {
        // Restaurer le bouton
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    });
});

// Fonctions pour le modal des détails de commande
function openOrderDetailsModal(orderId) {
    document.getElementById('orderDetailsModal').style.display = 'flex';
    document.getElementById('modalOrderId').textContent = orderId;
    document.getElementById('modalOrderItems').innerHTML = `
        <div class="text-center py-4">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
            <p class="text-gray-600 mt-2">Chargement des détails...</p>
        </div>
    `;
    
    // Configurer le bouton d'impression dans le modal
    const printBtn = document.getElementById('modalPrintReceiptBtn');
    if (printBtn) {
        printBtn.setAttribute('data-order-id', orderId);
    }
    
    const apiUrl = `/admin/orders/${orderId}/ajax`;
    
    fetch(apiUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur HTTP: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                populateOrderModalWithJSON(data.order, orderId);
            } else {
                throw new Error(data.message || 'Erreur inconnue du serveur');
            }
        })
        .catch(error => {
            document.getElementById('modalOrderItems').innerHTML = `
                <div class="text-center py-4 text-red-600">
                    ❌ ${error.message || 'Erreur lors du chargement des détails'}
                </div>
            `;
        });
}

function populateOrderModalWithJSON(orderData, orderId) {
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
}

function closeOrderDetailsModal() {
    document.getElementById('orderDetailsModal').style.display = 'none';
}

// Fonctions globales pour les modaux
function globalOpenAddModal() {
    document.getElementById('globalMenuForm').reset();
    document.getElementById('globalModalTitle').textContent = 'Ajouter un nouvel article';
    document.getElementById('globalSubmitText').textContent = 'Ajouter l\'article';
    document.getElementById('globalMethodField').innerHTML = '';
    document.getElementById('globalMenuForm').action = '{{ route("admin.menu.add") }}';
    document.getElementById('globalAddModal').style.display = 'flex';
}

function globalOpenAddModalWithCategory(category) {
    document.getElementById('globalMenuForm').reset();
    document.getElementById('globalModalTitle').textContent = 'Ajouter un nouvel article';
    document.getElementById('globalSubmitText').textContent = 'Ajouter l\'article';
    document.getElementById('globalMethodField').innerHTML = '';
    document.getElementById('globalMenuForm').action = '{{ route("admin.menu.add") }}';
    
    // Présélectionner la catégorie actuelle
    document.getElementById('globalItemCategory').value = category;
    
    document.getElementById('globalAddModal').style.display = 'flex';
}

function globalCloseModal() {
    document.getElementById('globalAddModal').style.display = 'none';
}

// Gestion de la soumission du formulaire global
document.getElementById('globalMenuForm')?.addEventListener('submit', async function(e) {
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
            showToast('✅ ' + result.message, 'success');
            
            // Recharger le contenu du menu avec la même catégorie
            setTimeout(() => {
                if (window.dashboardComponent) {
                    const savedCategory = localStorage.getItem('adminMenuCategory') || 'repas';
                    window.dashboardComponent.loadMenu(savedCategory);
                }
            }, 1000);
            
        } else {
            // Erreur
            showToast('❌ ' + result.message, 'error');
        }
        
    } catch (error) {
        console.error('Erreur réseau:', error);
        showToast('❌ Erreur réseau lors de la sauvegarde: ' + error.message, 'error');
    } finally {
        // Restaurer le bouton
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    }
});

// Fonctions pour les promotions
function openPromotionModal(item) {
    document.getElementById('promotionItemName').textContent = item.name;
    document.getElementById('promotionCurrentPrice').textContent = item.price + ' FCFA';
    document.getElementById('promotionOriginalPrice').value = item.price;
    document.getElementById('promotionItemId').value = item.id;
    document.getElementById('promotionDiscount').value = '';
    document.getElementById('promotionNewPrice').textContent = '- FCFA';
    document.getElementById('promotionModal').style.display = 'flex';
}

function closePromotionModal() {
    document.getElementById('promotionModal').style.display = 'none';
}

// Calcul du nouveau prix en temps réel pour les promotions
document.getElementById('promotionDiscount')?.addEventListener('input', function(e) {
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
document.getElementById('promotionForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitButton = document.getElementById('promotionSubmitButton');
    const originalText = submitButton.innerHTML;
    
    // Afficher un indicateur de chargement
    submitButton.innerHTML = '⏳ Application...';
    submitButton.disabled = true;
    
    try {
        const itemId = document.getElementById('promotionItemId').value;
        const response = await fetch(`/admin/menu/${itemId}/promotion`, {
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
            showToast('✅ ' + result.message, 'success');
            
            // Recharger le menu
            if (window.dashboardComponent) {
                const savedCategory = localStorage.getItem('adminMenuCategory') || 'repas';
                window.dashboardComponent.loadMenu(savedCategory);
            }
        } else {
            showToast('❌ ' + result.message, 'error');
        }
        
    } catch (error) {
        console.error('Erreur:', error);
        showToast('❌ Erreur réseau lors de l\'application de la promotion', 'error');
    } finally {
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    }
});

// ============================================================================
// FONCTIONS POUR LA GESTION DU MENU
// ============================================================================

// Fonction pour ouvrir le modal d'édition d'article
async function globalOpenEditModal(itemId) {
    try {
        console.log('Chargement article pour édition:', itemId);
        
        const response = await fetch(`/admin/menu/${itemId}/ajax`);
        const item = await response.json();
        
        if (item.error) {
            throw new Error(item.error);
        }

        // Remplir le formulaire avec les données de l'article
        document.getElementById('globalMenuForm').reset();
        document.getElementById('globalModalTitle').textContent = 'Modifier l\'article';
        document.getElementById('globalSubmitText').textContent = 'Modifier l\'article';
        document.getElementById('globalMethodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';
        document.getElementById('globalMenuForm').action = `/admin/menu/${itemId}`;
        
        document.getElementById('globalItemName').value = item.name || '';
        document.getElementById('globalItemDescription').value = item.description || '';
        document.getElementById('globalItemPrice').value = item.price || '';
        document.getElementById('globalItemCategory').value = item.category || 'repas';
        document.getElementById('globalItemAvailable').checked = item.available !== false;
        
        document.getElementById('globalAddModal').style.display = 'flex';
        
    } catch (error) {
        console.error('Erreur chargement article:', error);
        showToast('❌ Erreur lors du chargement de l\'article: ' + error.message, 'error');
    }
}

// Fonction pour supprimer un article
async function deleteMenuItem(itemId) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cet article ? Cette action est irréversible.')) {
        return;
    }

    try {
        const response = await fetch(`/admin/menu/${itemId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const result = await response.json();

        if (result.success) {
            showToast('✅ ' + result.message, 'success');
            
            // Recharger le menu
            if (window.dashboardComponent) {
                const savedCategory = localStorage.getItem('adminMenuCategory') || 'repas';
                window.dashboardComponent.loadMenu(savedCategory);
            }
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Erreur suppression:', error);
        showToast('❌ Erreur lors de la suppression: ' + error.message, 'error');
    }
}

// Fonction pour ouvrir le modal de promotion avec les données de l'article
async function openPromotionModalForItem(itemId) {
    try {
        console.log('Ouverture promotion pour article:', itemId);
        
        const response = await fetch(`/admin/menu/${itemId}/ajax`);
        const item = await response.json();
        
        if (item.error) {
            throw new Error(item.error);
        }

        // Remplir le modal de promotion
        document.getElementById('promotionItemName').textContent = item.name;
        document.getElementById('promotionCurrentPrice').textContent = item.price + ' FCFA';
        document.getElementById('promotionOriginalPrice').value = item.price;
        document.getElementById('promotionItemId').value = item.id;
        document.getElementById('promotionDiscount').value = '';
        document.getElementById('promotionNewPrice').textContent = '- FCFA';
        document.getElementById('promotionModal').style.display = 'flex';
        
    } catch (error) {
        console.error('Erreur chargement article pour promotion:', error);
        showToast('❌ Erreur lors du chargement de l\'article: ' + error.message, 'error');
    }
}

// Fonction pour retirer une promotion
async function removePromotion(itemId) {
    if (!confirm('Êtes-vous sûr de vouloir retirer la promotion de cet article ?')) {
        return;
    }

    try {
        const response = await fetch(`/admin/menu/${itemId}/promotion`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const result = await response.json();

        if (result.success) {
            showToast('✅ ' + result.message, 'success');
            
            // Recharger le menu
            if (window.dashboardComponent) {
                const savedCategory = localStorage.getItem('adminMenuCategory') || 'repas';
                window.dashboardComponent.loadMenu(savedCategory);
            }
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Erreur retrait promotion:', error);
        showToast('❌ Erreur lors du retrait de la promotion: ' + error.message, 'error');
    }
}

// Fonction pour basculer la disponibilité
async function toggleAvailability(itemId) {
    try {
        const response = await fetch(`/admin/menu/${itemId}/toggle`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const result = await response.json();

        if (result.success) {
            showToast('✅ ' + result.message, 'success');
            
            // Recharger le menu
            if (window.dashboardComponent) {
                const savedCategory = localStorage.getItem('adminMenuCategory') || 'repas';
                window.dashboardComponent.loadMenu(savedCategory);
            }
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Erreur bascule disponibilité:', error);
        showToast('❌ Erreur lors du changement de disponibilité: ' + error.message, 'error');
    }
}

// Fonctions compatibles avec votre composant menu-item-card
function handleEditItem(item) {
    globalOpenEditModal(item.id);
}

function handleDeleteItem(itemId) {
    deleteMenuItem(itemId);
}

function handleAddPromotion(itemId) {
    openPromotionModalForItem(itemId);
}

function handleRemovePromotion(itemId) {
    removePromotion(itemId);
}

function handleToggleAvailability(itemId) {
    toggleAvailability(itemId);
}

// Fonctions pour le modal clients
function openAddClientModal() {
    console.log('Ouverture du modal clients');
    document.getElementById('addClientModal').style.display = 'flex';
    
    // Charger les clients disponibles
    fetch('/admin/clients/available')
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur HTTP: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            const container = document.getElementById('availableClientsList');
            if (data.success && data.clients && data.clients.length > 0) {
                container.innerHTML = data.clients.map(client => `
                    <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                        <div class="flex items-center space-x-4">
                            <input type="checkbox" 
                                   value="${client.id}" 
                                   class="client-checkbox w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                <span class="text-blue-600 font-semibold text-sm">${client.name ? client.name.substring(0, 2).toUpperCase() : 'CL'}</span>
                            </div>
                            
                            <div>
                                <h4 class="font-semibold text-gray-800">${client.name || 'Client sans nom'}</h4>
                                <p class="text-sm text-gray-500">${client.email || 'Aucun email'}</p>
                                <p class="text-xs text-gray-400">Table #${client.table_number || 'N/A'}</p>
                            </div>
                        </div>
                        
                        <div class="text-right">
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium ${client.is_suspended ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'}">
                                ${client.is_suspended ? 'Suspendu' : 'Actif'}
                            </span>
                        </div>
                    </div>
                `).join('');
            } else {
                container.innerHTML = `
                    <div class="text-center py-8">
                        <div class="text-6xl mb-4 text-gray-300">👥</div>
                        <h3 class="text-xl font-semibold text-gray-500 mb-2">Aucun client disponible</h3>
                        <p class="text-gray-400">Tous les clients sont déjà liés à votre compte</p>
                    </div>
                `;
            }
            
            // Réinitialiser les sélections
            updateSelectedCount();
        })
        .catch(error => {
            console.error('Erreur:', error);
            document.getElementById('availableClientsList').innerHTML = `
                <div class="text-center py-8 text-red-600">
                    Erreur lors du chargement des clients: ${error.message}
                </div>
            `;
        });
}

function closeAddClientModal() {
    document.getElementById('addClientModal').style.display = 'none';
}

function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.client-checkbox:checked');
    const count = checkboxes.length;
    const button = document.getElementById('linkClientsButton');
    const counter = document.getElementById('selectedCount');
    
    counter.textContent = `${count} client(s) sélectionné(s)`;
    
    if (count > 0) {
        button.textContent = `Lier ${count} client(s)`;
        button.className = 'px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition-all duration-300';
        button.disabled = false;
    } else {
        button.textContent = 'Lier 0 client(s)';
        button.className = 'px-4 py-2 bg-blue-400 text-white rounded-lg font-semibold transition-all duration-300 cursor-not-allowed';
        button.disabled = true;
    }
}

async function linkSelectedClients() {
    const checkboxes = document.querySelectorAll('.client-checkbox:checked');
    const clientIds = Array.from(checkboxes).map(checkbox => checkbox.value);
    
    if (clientIds.length === 0) return;

    const button = document.getElementById('linkClientsButton');
    const originalText = button.textContent;
    
    button.textContent = 'Liaison en cours...';
    button.disabled = true;

    try {
        const response = await fetch('/admin/clients/link', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                client_ids: clientIds
            })
        });

        const result = await response.json();

        if (result.success) {
            showToast(result.message, 'success');
            closeAddClientModal();
            // Recharger l'onglet clients
            if (window.dashboardComponent) {
                window.dashboardComponent.loadClients();
            }
        } else {
            throw new Error(result.message);
        }

    } catch (error) {
        console.error('Erreur:', error);
        showToast('Erreur lors de la liaison des clients: ' + error.message, 'error');
    } finally {
        button.textContent = originalText;
        button.disabled = false;
    }
}

// Fonctions pour la gestion des clients
async function suspendClient(clientId) {
    if (!confirm('Êtes-vous sûr de vouloir suspendre ce client ?')) return;
    
    try {
        const response = await fetch(`/admin/clients/${clientId}/suspend`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message, 'success');
            // Recharger l'onglet clients
            if (window.dashboardComponent) {
                window.dashboardComponent.loadClients();
            }
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        showToast('Erreur: ' + error.message, 'error');
    }
}

async function activateClient(clientId) {
    try {
        const response = await fetch(`/admin/clients/${clientId}/activate`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message, 'success');
            // Recharger l'onglet clients
            if (window.dashboardComponent) {
                window.dashboardComponent.loadClients();
            }
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        showToast('Erreur: ' + error.message, 'error');
    }
}

async function unlinkClient(clientId) {
    if (!confirm('Êtes-vous sûr de vouloir retirer ce client ?')) return;
    
    try {
        const response = await fetch(`/admin/clients/${clientId}/unlink`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message, 'success');
            // Recharger l'onglet clients
            if (window.dashboardComponent) {
                window.dashboardComponent.loadClients();
            }
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        showToast('Erreur: ' + error.message, 'error');
    }
}

// ============================================================================
// FONCTIONS POUR LE TÉLÉCHARGEMENT DES RAPPORTS
// ============================================================================

// Fonction pour télécharger le rapport PDF
async function downloadDateReport() {
    const reportDate = document.getElementById('reportDate')?.value;
    const button = document.getElementById('downloadReportBtn');
    const originalText = button.innerHTML;

    if (!reportDate) {
        alert('Veuillez d\'abord générer un rapport');
        return;
    }

    // Afficher l'indicateur de chargement
    button.innerHTML = '⏳ Génération du PDF...';
    button.disabled = true;

    try {
        console.log('📥 Téléchargement du rapport pour:', reportDate);
        
        // Créer un formulaire pour envoyer les données
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/admin/reports/download-date-report';
        
        // Ajouter le token CSRF
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        form.appendChild(csrfToken);
        
        // Ajouter la date du rapport
        const dateInput = document.createElement('input');
        dateInput.type = 'hidden';
        dateInput.name = 'report_date';
        dateInput.value = reportDate;
        form.appendChild(dateInput);
        
        // Ajouter le formulaire au document et le soumettre
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
        
    } catch (error) {
        console.error('❌ Erreur téléchargement rapport:', error);
        alert('Erreur lors du téléchargement: ' + error.message);
    } finally {
        // Restaurer le bouton après un court délai
        setTimeout(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        }, 2000);
    }
}

// Fonctions pour le modal de rapport par date
function closeDateReportModal() {
    document.getElementById('dateReportModal').style.display = 'none';
}

// Gestion des événements
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('client-checkbox')) {
        updateSelectedCount();
    }
});

// Fermer les modaux en cliquant à l'extérieur
document.addEventListener('click', function(e) {
    if (e.target.id === 'timeModal') closeTimeModal();
    if (e.target.id === 'addTimeModal') closeAddTimeModal();
    if (e.target.id === 'globalAddModal') globalCloseModal();
    if (e.target.id === 'promotionModal') closePromotionModal();
    if (e.target.id === 'orderDetailsModal') closeOrderDetailsModal();
    if (e.target.id === 'addClientModal') closeAddClientModal();
    if (e.target.id === 'dateReportModal') closeDateReportModal();
});

// Fermer avec Échap
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeTimeModal();
        closeAddTimeModal();
        globalCloseModal();
        closePromotionModal();
        closeOrderDetailsModal();
        closeAddClientModal();
        closeDateReportModal();
    }
});

// Délégation d'événements globale pour les boutons d'impression
document.addEventListener('click', function(e) {
    // Bouton Imprimer Reçu dans les cartes de commande
    if (e.target.classList.contains('print-receipt-btn')) {
        e.preventDefault();
        const orderId = e.target.getAttribute('data-order-id');
        printReceipt(orderId);
    }
    
    // Bouton Prévisualiser Reçu (optionnel)
    if (e.target.classList.contains('preview-receipt-btn')) {
        e.preventDefault();
        const orderId = e.target.getAttribute('data-order-id');
        previewReceipt(orderId);
    }
});

// Fonction utilitaire pour les toats
function showToast(message, type = 'success', duration = 4000) {
    const existingToasts = document.querySelectorAll('.custom-toast');
    existingToasts.forEach(toast => toast.remove());

    const toast = document.createElement('div');
    toast.className = `custom-toast fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white font-semibold z-50 ${
        type === 'success' ? 'bg-green-500' : 'bg-red-500'
    }`;
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => {
        if (toast.parentNode) {
            toast.style.transition = 'opacity 0.5s ease';
            toast.style.opacity = '0';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 500);
        }
    }, duration);
}

// Fonction pour mettre à jour le statut de commande via AJAX
async function updateOrderStatus(orderId, newStatus) {
    try {
        const response = await fetch(`/admin/orders/${orderId}/status-ajax`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                status: newStatus
            })
        });

        const result = await response.json();

        if (result.success) {
            showToast('✅ ' + result.message, 'success');
            
            // Recharger seulement le contenu des commandes
            if (window.dashboardComponent) {
                const currentStatus = localStorage.getItem('adminOrdersStatus') || 'pending';
                window.dashboardComponent.loadOrders(currentStatus);
            }
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Erreur:', error);
        showToast('❌ Erreur: ' + error.message, 'error');
    }
}

// Composant principal du dashboard - VERSION CORRIGÉE
document.addEventListener('alpine:init', () => {
    Alpine.data('dashboardComponent', () => ({
        activeTab: 'overview',
        ordersContent: '',
        menuContent: '',
        clientsContent: '',
        reportsContent: '',
        loading: false,
        error: false,
        
        init() {
            window.dashboardComponent = this;
            console.log('Dashboard component initialized');
            
            // Récupérer l'onglet depuis le localStorage
            const savedTab = localStorage.getItem('adminActiveTab');
            if (savedTab && ['overview', 'orders', 'menu', 'clients', 'reports'].includes(savedTab)) {
                this.activeTab = savedTab;
                // Charger le contenu de l'onglet sauvegardé
                this.$nextTick(() => {
                    this.switchTab(savedTab);
                });
            }
        },
        
        async switchTab(tabName) {
            this.activeTab = tabName;
            this.loading = true;
            this.error = false;
            
            // Sauvegarder l'onglet dans le localStorage
            localStorage.setItem('adminActiveTab', tabName);
            
            await this.$nextTick();
            
            try {
                if (tabName === 'orders') {
                    await this.loadOrders();
                } else if (tabName === 'menu') {
                    await this.loadMenu();
                } else if (tabName === 'clients') {
                    await this.loadClients();
                } else if (tabName === 'reports') {
                    await this.loadReports();
                }
            } catch (error) {
                console.error('Erreur changement d\'onglet:', error);
                this.error = true;
            } finally {
                this.loading = false;
            }
        },
        
        async loadOrders(status = null) {
            try {
                // Utiliser le statut sauvegardé ou celui fourni
                const savedStatus = localStorage.getItem('adminOrdersStatus') || 'pending';
                const ordersStatus = status || savedStatus;
                
                console.log('Chargement des commandes, statut:', ordersStatus);
                const response = await fetch(`/admin/orders/ajax?status=${ordersStatus}&_=${Date.now()}`);
                
                if (!response.ok) {
                    throw new Error(`Erreur HTTP: ${response.status}`);
                }
                
                const html = await response.text();
                this.ordersContent = html;
                console.log('Commandes chargées avec succès');
                
                // Sauvegarder le statut
                localStorage.setItem('adminOrdersStatus', ordersStatus);
                
            } catch (error) {
                console.error('Erreur de chargement des commandes:', error);
                this.error = true;
                this.ordersContent = `
                    <div class="text-center py-8 text-red-600">
                        <p>Erreur de chargement des commandes</p>
                        <button onclick="window.dashboardComponent.loadOrders()" 
                                class="mt-4 bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                            Réessayer
                        </button>
                    </div>
                `;
            }
        },
        
        async loadMenu(category = null) {
            try {
                // Utiliser la catégorie sauvegardée ou celle fournie
                const savedCategory = localStorage.getItem('adminMenuCategory') || 'repas';
                const menuCategory = category || savedCategory;
                
                console.log('Chargement du menu, catégorie:', menuCategory);
                const response = await fetch(`/admin/menu/ajax?category=${menuCategory}&_=${Date.now()}`);
                
                if (!response.ok) {
                    throw new Error(`Erreur HTTP: ${response.status}`);
                }
                
                const html = await response.text();
                this.menuContent = html;
                console.log('Menu chargé avec succès');
                
                // Sauvegarder la catégorie
                localStorage.setItem('adminMenuCategory', menuCategory);
                
            } catch (error) {
                console.error('Erreur de chargement du menu:', error);
                this.error = true;
                this.menuContent = `
                    <div class="text-center py-8 text-red-600">
                        <p>Erreur de chargement du menu</p>
                        <button onclick="window.dashboardComponent.loadMenu()" 
                                class="mt-4 bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                            Réessayer
                        </button>
                    </div>
                `;
            }
        },
        
        async loadClients() {
            try {
                console.log('Chargement des clients');
                const response = await fetch(`/admin/clients/ajax?_=${Date.now()}`);
                
                if (!response.ok) {
                    throw new Error(`Erreur HTTP: ${response.status}`);
                }
                
                const html = await response.text();
                this.clientsContent = html;
                console.log('Clients chargés avec succès');
                
            } catch (error) {
                console.error('Erreur de chargement des clients:', error);
                this.error = true;
                this.clientsContent = `
                    <div class="text-center py-8 text-red-600">
                        <p>Erreur de chargement des clients</p>
                        <button onclick="window.dashboardComponent.loadClients()" 
                                class="mt-4 bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                            Réessayer
                        </button>
                    </div>
                `;
            }
        },
        
        async loadReports(startDate = null, endDate = null) {
            try {
                console.log('Chargement des rapports');
                let url = `/admin/reports/ajax?_=${Date.now()}`;
                if (startDate && endDate) {
                    url += `&start_date=${startDate}&end_date=${endDate}`;
                }
                
                const response = await fetch(url);
                
                if (!response.ok) {
                    throw new Error(`Erreur HTTP: ${response.status}`);
                }
                
                const html = await response.text();
                this.reportsContent = html;
                console.log('Rapports chargés avec succès');
                
                // Initialiser les graphiques après le chargement
                this.$nextTick(() => {
                    this.initReportsChart();
                });
                
            } catch (error) {
                console.error('Erreur de chargement des rapports:', error);
                this.error = true;
                this.reportsContent = `
                    <div class="text-center py-8 text-red-600">
                        <p>Erreur de chargement des rapports</p>
                        <button onclick="window.dashboardComponent.loadReports()" 
                                class="mt-4 bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                            Réessayer
                        </button>
                    </div>
                `;
            }
        },

        // FONCTIONS POUR LES RAPPORTS
        async generateDateReport() {
            const reportDate = document.getElementById('reportDate')?.value;
            const button = document.getElementById('generateReportBtn');
            const buttonText = document.getElementById('reportBtnText');
            const buttonLoading = document.getElementById('reportBtnLoading');

            if (!reportDate) {
                alert('Veuillez sélectionner une date');
                return;
            }

            // Afficher l'indicateur de chargement
            if (button) {
                button.disabled = true;
                if (buttonText) buttonText.classList.add('hidden');
                if (buttonLoading) buttonLoading.classList.remove('hidden');
            }

            try {
                console.log('📊 Génération du rapport pour:', reportDate);
                
                const response = await fetch('/admin/reports/generate-date-report', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        report_date: reportDate
                    })
                });

                const result = await response.json();

                if (result.success) {
                    console.log('✅ Rapport généré avec succès:', result.report);
                    this.showDateReportModal(result.report);
                } else {
                    throw new Error(result.message || 'Erreur lors de la génération du rapport');
                }

            } catch (error) {
                console.error('❌ Erreur génération rapport:', error);
                alert('Erreur: ' + error.message);
            } finally {
                // Restaurer le bouton
                if (button) {
                    button.disabled = false;
                    if (buttonText) buttonText.classList.remove('hidden');
                    if (buttonLoading) buttonLoading.classList.add('hidden');
                }
            }
        },

        showDateReportModal(report) {
            const modal = document.getElementById('dateReportModal');
            const title = document.getElementById('modalReportTitle');
            const content = document.getElementById('dateReportContent');
            
            // Mettre à jour le titre
            if (title) title.textContent = `Rapport du ${report.formatted_date}`;
            
            // Générer le contenu du rapport
            if (content) content.innerHTML = this.generateReportHTML(report);
            
            // Stocker la date du rapport pour le téléchargement
            modal.setAttribute('data-report-date', report.date);
            
            // Afficher le modal
            if (modal) modal.style.display = 'flex';
        },

        generateReportHTML(report) {
            return `
                <div class="space-y-6">
                    <!-- Métriques principales -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-blue-50 rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-blue-600">${report.total_orders}</div>
                            <div class="text-sm text-blue-800">Commandes totales</div>
                        </div>
                        <div class="bg-green-50 rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-green-600">${report.total_revenue ? report.total_revenue.toLocaleString('fr-FR') : 0} FCFA</div>
                            <div class="text-sm text-green-800">Chiffre d'affaires</div>
                        </div>
                        <div class="bg-purple-50 rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-purple-600">${report.total_orders > 0 && report.total_revenue ? Math.round(report.total_revenue / report.total_orders).toLocaleString('fr-FR') : 0} FCFA</div>
                            <div class="text-sm text-purple-800">Panier moyen</div>
                        </div>
                    </div>

                    <!-- Analyse des revenus -->
                    <div class="bg-white rounded-lg border p-6">
                        <h4 class="text-lg font-semibold mb-4">📈 Analyse des Revenus</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span>Sur place:</span>
                                    <span class="font-semibold">${report.revenue_analysis.sur_place ? report.revenue_analysis.sur_place.toLocaleString('fr-FR') : 0} FCFA</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Livraison:</span>
                                    <span class="font-semibold">${report.revenue_analysis.livraison ? report.revenue_analysis.livraison.toLocaleString('fr-FR') : 0} FCFA</span>
                                </div>
                                <div class="flex justify-between border-t pt-2 font-bold">
                                    <span>Total:</span>
                                    <span class="text-blue-600">${report.revenue_analysis.total ? report.revenue_analysis.total.toLocaleString('fr-FR') : 0} FCFA</span>
                                </div>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="text-sm text-gray-600 mb-2">Répartition</div>
                                <div class="space-y-1">
                                    <div class="flex justify-between text-sm">
                                        <span>Sur place:</span>
                                        <span>${report.revenue_analysis.total > 0 ? Math.round((report.revenue_analysis.sur_place / report.revenue_analysis.total) * 100) : 0}%</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span>Livraison:</span>
                                        <span>${report.revenue_analysis.total > 0 ? Math.round((report.revenue_analysis.livraison / report.revenue_analysis.total) * 100) : 0}%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Performance du menu -->
                    <div class="bg-white rounded-lg border p-6">
                        <h4 class="text-lg font-semibold mb-4">🍽️ Performance du Menu</h4>
                        <div class="space-y-3">
                            ${Object.values(report.menu_performance).length > 0 ? 
                                Object.values(report.menu_performance)
                                    .sort((a, b) => b.totalRevenue - a.totalRevenue)
                                    .slice(0, 10)
                                    .map(item => `
                                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                            <div class="flex items-center gap-3">
                                                <span class="px-2 py-1 text-xs rounded ${
                                                    item.category === 'repas' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'
                                                }">
                                                    ${item.category}
                                                </span>
                                                <span class="font-medium">${item.name}</span>
                                            </div>
                                            <div class="text-right">
                                                <div class="font-semibold">${item.totalRevenue.toLocaleString('fr-FR')} FCFA</div>
                                                <div class="text-sm text-gray-600">
                                                    ${item.totalQuantity} vendus • ${item.orders} commandes
                                                </div>
                                            </div>
                                        </div>
                                    `).join('') 
                                : 
                                '<div class="text-center py-4 text-gray-500">Aucune donnée de vente pour cette date</div>'
                            }
                        </div>
                    </div>

                    <!-- Statut des commandes -->
                    <div class="bg-white rounded-lg border p-6">
                        <h4 class="text-lg font-semibold mb-4">📊 Statut des Commandes</h4>
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                            ${Object.entries(report.order_status).map(([status, count]) => `
                                <div class="text-center p-3 bg-gray-50 rounded-lg">
                                    <div class="text-xl font-bold ${
                                        status === 'terminé' || status === 'livré' ? 'text-green-600' : 
                                        status === 'prêt' ? 'text-blue-600' : 'text-orange-600'
                                    }">${count}</div>
                                    <div class="text-sm text-gray-600 capitalize">${status.replace('_', ' ')}</div>
                                </div>
                            `).join('')}
                        </div>
                    </div>

                    <!-- Commandes par heure -->
                    <div class="bg-white rounded-lg border p-6">
                        <h4 class="text-lg font-semibold mb-4">⏰ Commandes par Heure</h4>
                        <div class="grid grid-cols-2 md:grid-cols-6 gap-2">
                            ${report.orders_by_hour.filter(hour => hour.count > 0).map(hour => `
                                <div class="text-center p-2 bg-blue-50 rounded">
                                    <div class="font-semibold">${hour.hour}</div>
                                    <div class="text-sm text-blue-600">${hour.count} cmd</div>
                                    <div class="text-xs text-gray-600">${hour.revenue.toLocaleString('fr-FR')} FCFA</div>
                                </div>
                            `).join('')}
                        </div>
                        ${report.orders_by_hour.filter(hour => hour.count > 0).length === 0 ? 
                            '<div class="text-center py-4 text-gray-500">Aucune commande enregistrée pour cette date</div>' : 
                            ''
                        }
                    </div>
                </div>
            `;
        },

        closeDateReportModal() {
            const modal = document.getElementById('dateReportModal');
            if (modal) modal.style.display = 'none';
        },

        initReportsChart() {
            console.log('🎨 Tentative de rendu du graphique...');
            const ctx = document.getElementById('reportsChart');
            
            if (!ctx) {
                console.error('❌ Canvas pour le graphique non trouvé');
                return;
            }
            
            // Les données du graphique seront chargées dynamiquement
            // Cette fonction sera appelée après le chargement du contenu des rapports
            setTimeout(() => {
                this.renderChart();
            }, 100);
        },

        renderChart() {
            const ctx = document.getElementById('reportsChart');
            if (!ctx) return;

            // Récupérer les données du graphique depuis les attributs data
            const chartDataElement = document.querySelector('[data-chart-data]');
            if (!chartDataElement) return;

            try {
                const chartData = JSON.parse(chartDataElement.getAttribute('data-chart-data'));
                console.log('📊 Données pour le graphique:', chartData);

                if (chartData.length === 0) {
                    console.log('Aucune donnée pour le graphique');
                    return;
                }

                if (typeof Chart === 'undefined') {
                    console.error('❌ Chart.js non disponible');
                    return;
                }

                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: chartData.map(item => item.name),
                        datasets: [{
                            label: 'Revenus (FCFA)',
                            data: chartData.map(item => item.totalRevenue),
                            backgroundColor: chartData.map(item => 
                                item.category === 'repas' ? 'rgba(249, 115, 22, 0.8)' : 'rgba(239, 68, 68, 0.8)'
                            ),
                            borderColor: chartData.map(item =>
                                item.category === 'repas' ? 'rgb(249, 115, 22)' : 'rgb(239, 68, 68)'
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
                                        const item = chartData[context.dataIndex];
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
            }
        }
    }));
});

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    console.log('Dashboard loaded');
    
    // Événements pour le modal d'ajout de temps
    document.getElementById('cancelAddTime')?.addEventListener('click', closeAddTimeModal);
    
    document.getElementById('confirmAddTime')?.addEventListener('click', function() {
        const orderId = document.getElementById('addTimeModal').getAttribute('data-order-id');
        const additionalTime = parseInt(document.getElementById('additionalTime').value);
        console.log('✅ Confirmation ajout temps:', orderId, additionalTime);
        addTimeToOrder(orderId, additionalTime);
    });
    
    document.getElementById('additionalTime')?.addEventListener('change', updateNewTimeDisplay);
    
    // Fermer le modal en cliquant à l'extérieur
    document.getElementById('addTimeModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeAddTimeModal();
        }
    });
    
    // Délégation d'événements globale
    document.addEventListener('click', function(e) {
        // Voir détails commande
        if (e.target.classList.contains('view-order-details-btn')) {
            const orderId = e.target.dataset.orderId;
            openOrderDetailsModal(orderId);
        }
        
        // Accepter commande (utilise le modal de temps)
        if (e.target.classList.contains('accept-order-btn')) {
            const orderId = e.target.dataset.orderId;
            openTimeModal(orderId);
        }

        // AJOUT : Boutons "Ajouter du Temps"
        if (e.target.classList.contains('add-time-btn')) {
            e.preventDefault();
            const orderId = e.target.getAttribute('data-order-id');
            const currentTime = e.target.getAttribute('data-current-time') || 0;
            console.log('⏱️ Clic sur bouton ajouter temps:', orderId);
            openAddTimeModal(orderId, parseInt(currentTime));
        }

        // AJOUT : Boutons "Imprimer Reçu"
        if (e.target.classList.contains('print-receipt-btn')) {
            e.preventDefault();
            const orderId = e.target.getAttribute('data-order-id');
            printReceipt(orderId);
        }

        // Ajouter client
        if (e.target.closest('button') && e.target.closest('button').textContent.includes('Ajouter Client')) {
            e.preventDefault();
            openAddClientModal();
        }

        // Ajouter article
        if (e.target.closest('button') && (e.target.closest('button').textContent.includes('Ajouter un Article') || e.target.closest('button').textContent.includes('Ajouter le premier') || e.target.closest('button').textContent.includes('Ajouter la première'))) {
            e.preventDefault();
            const savedCategory = localStorage.getItem('adminMenuCategory') || 'repas';
            globalOpenAddModalWithCategory(savedCategory);
        }

        // Boutons de catégorie menu
        if (e.target.hasAttribute('data-category')) {
            e.preventDefault();
            const category = e.target.getAttribute('data-category');
            console.log('Changement de catégorie:', category);
            
            // Sauvegarder la catégorie
            localStorage.setItem('adminMenuCategory', category);
            
            if (window.dashboardComponent) {
                window.dashboardComponent.loadMenu(category);
            }
        }

        // Boutons de statut commandes
        if (e.target.hasAttribute('data-status')) {
            e.preventDefault();
            const status = e.target.getAttribute('data-status');
            console.log('Changement de statut:', status);
            
            // Sauvegarder le statut
            localStorage.setItem('adminOrdersStatus', status);
            
            if (window.dashboardComponent) {
                window.dashboardComponent.loadOrders(status);
            }
        }
    });
});
</script>

<style>
[x-cloak] { display: none !important; }

.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from, .fade-leave-to {
    opacity: 0;
}

.tooltip {
    position: relative;
}

.tooltip:hover::after {
    content: attr(title);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: #333;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    white-space: nowrap;
    z-index: 1000;
    margin-bottom: 5px;
}

.custom-toast {
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Styles pour le bouton d'impression */
.print-receipt-btn {
    background: linear-gradient(135deg, #1e40af 0%, #1d4ed8 100%);
    transition: all 0.3s ease;
}

.print-receipt-btn:hover {
    background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

/* Styles pour le bouton de téléchargement */
#downloadReportBtn {
    background: linear-gradient(135deg, #059669 0%, #10b981 100%);
    transition: all 0.3s ease;
}

#downloadReportBtn:hover {
    background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

/* Animation de chargement pour l'impression */
.print-loading {
    position: relative;
    overflow: hidden;
}

.print-loading::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    animation: loading 1.5s infinite;
}

@keyframes loading {
    0% { left: -100%; }
    100% { left: 100%; }
}

/* Styles pour les notifications de timer */
.timer-notification {
    animation: slideDown 0.3s ease-out;
    z-index: 10;
}

@keyframes slideDown {
    from {
        transform: translateY(-100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Animation de pulse pour les timers */
@keyframes pulse-orange {
    0%, 100% {
        box-shadow: 0 0 0 0 rgba(249, 115, 22, 0.7);
    }
    50% {
        box-shadow: 0 0 0 10px rgba(249, 115, 22, 0);
    }
}

@keyframes pulse-red {
    0%, 100% {
        box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
    }
    50% {
        box-shadow: 0 0 0 10px rgba(239, 68, 68, 0);
    }
}

.animate-pulse-orange {
    animation: pulse-orange 2s infinite;
}

.animate-pulse-red {
    animation: pulse-red 1s infinite;
}

/* Transitions pour les barres de progression */
.bg-blue-600, .bg-orange-500, .bg-red-500 {
    transition: width 1s ease-in-out;
}
</style>
@endsection
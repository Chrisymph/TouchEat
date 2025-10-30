<div class="space-y-6" id="orders-content-container">
    <div class="flex justify-between items-center">
        <h2 class="text-3xl font-bold">Gestion des Commandes</h2>
        <div class="flex space-x-4">
            <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-semibold">
                {{ $orderCounts['pending'] }} en attente
            </span>
            <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-semibold">
                {{ $orderCounts['ready'] }} prêtes
            </span>
        </div>
    </div>

    <!-- Onglets des commandes -->
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
            <button data-status="pending" 
                   class="{{ $status === 'pending' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }} whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                En Attente ({{ $orderCounts['pending'] }})
            </button>
            <button data-status="ready" 
                   class="{{ $status === 'ready' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }} whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                Prêtes ({{ $orderCounts['ready'] }})
            </button>
            <button data-status="completed" 
                   class="{{ $status === 'completed' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }} whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                Terminées ({{ $orderCounts['completed'] }})
            </button>
        </nav>
    </div>

    <!-- Liste des commandes -->
    @if($orders->isEmpty())
        <div class="bg-white rounded-lg shadow text-center py-12">
            <div class="text-6xl mb-4">
                @if($status === 'pending') ⏳
                @elseif($status === 'ready') ✅
                @else 📜 @endif
            </div>
            <h3 class="text-xl font-semibold text-gray-600 mb-2">
                @if($status === 'pending') Aucune commande en attente
                @elseif($status === 'ready') Aucune commande prête
                @else Aucune commande terminée aujourd'hui @endif
            </h3>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($orders as $order)
            <div class="bg-white rounded-lg shadow transition-all duration-200 hover:shadow-lg">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-semibold">#{{ $order->id }}</h3>
                            <p class="text-gray-600 text-sm">
                                Table {{ $order->table_number }} • 
                                {{ $order->created_at->format('H:i') }}
                            </p>
                        </div>
                        <span class="px-2 py-1 rounded-full text-xs font-semibold 
                            @if($order->status === 'commandé') bg-yellow-100 text-yellow-800
                            @elseif($order->status === 'en_cours') bg-blue-100 text-blue-800
                            @elseif($order->status === 'prêt') bg-green-100 text-green-800
                            @else bg-gray-100 text-gray-800 @endif">
                            {{ ucfirst($order->status) }}
                        </span>
                    </div>

                    <!-- Articles -->
                    <div class="space-y-2 mb-4">
                        @foreach($order->items as $item)
                        <div class="flex justify-between text-sm">
                            <span>{{ $item->menuItem->name ?? 'Article' }} x{{ $item->quantity }}</span>
                            <span class="font-semibold">
                                {{ number_format($item->unit_price * $item->quantity, 0, ',', ' ') }} FCFA
                            </span>
                        </div>
                        @endforeach
                    </div>

                    <!-- Total -->
                    <div class="border-t pt-2 mb-4">
                        <div class="flex justify-between font-bold">
                            <span>Total</span>
                            <span class="text-lg text-blue-600">
                                {{ number_format($order->total, 0, ',', ' ') }} FCFA
                            </span>
                        </div>
                    </div>

                    <!-- CORRIGÉ : Détection améliorée des ajouts d'articles -->
                    @php
                        // Vérifier si des articles ont été ajoutés après l'acceptation
                        $hasRecentAdditions = false;
                        $orderAge = $order->created_at->diffInMinutes(now());
                        
                        if ($orderAge > 5) {
                            // Vérifier s'il y a des items créés récemment (dans les 5 dernières minutes)
                            $recentItems = $order->items->filter(function($item) {
                                return $item->created_at->diffInMinutes(now()) <= 5;
                            });
                            $hasRecentAdditions = $recentItems->count() > 0;
                        }
                        
                        $hasMultipleItems = $order->items->count() > 1;
                        $hasLargeQuantity = $order->items->sum('quantity') > 3;
                        $hasAdditions = $hasRecentAdditions || $hasMultipleItems || $hasLargeQuantity;
                    @endphp
                    
                    @if($hasAdditions && in_array($order->status, ['commandé', 'en_cours']))
                    <div class="mb-3">
                        <span class="inline-flex items-center px-2 py-1 bg-purple-100 text-purple-800 rounded-full text-xs font-semibold">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"/>
                            </svg>
                            Ajouts d'articles
                        </span>
                    </div>
                    @endif

                    <!-- Actions -->
                    <div class="flex space-x-2">
                        <button type="button" 
                                class="flex-1 bg-gray-100 text-gray-700 px-3 py-2 rounded text-sm text-center hover:bg-gray-200 transition-colors view-order-details-btn"
                                data-order-id="{{ $order->id }}">
                            Voir Détails
                        </button>
                        
                        @if($order->status === 'commandé')
                        <button type="button" 
                                class="flex-1 bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700 transition-colors accept-order-btn"
                                data-order-id="{{ $order->id }}">
                            Accepter
                        </button>
                        @elseif(in_array($order->status, ['en_cours', 'prêt']))
                        <form action="{{ route('admin.orders.status', $order->id) }}" method="POST" class="flex-1">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="{{ 
                                $order->status === 'en_cours' ? 'prêt' : 'terminé'
                            }}">
                            <button type="submit" class="w-full bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700 transition-colors">
                                {{ $order->status === 'en_cours' ? 'Marquer Prêt' : 'Terminer' }}
                            </button>
                        </form>
                        @endif
                    </div>

                    <!-- CORRIGÉ : Bouton Ajouter du Temps - Conditions plus simples -->
                    @if($hasAdditions && in_array($order->status, ['commandé', 'en_cours']) && $order->status !== 'prêt')
                    <div class="mt-3 pt-3 border-t border-gray-200">
                        <button type="button" 
                                class="w-full bg-purple-600 text-white px-3 py-2 rounded text-sm hover:bg-purple-700 transition-colors add-time-btn"
                                data-order-id="{{ $order->id }}"
                                data-current-time="{{ $order->estimated_time ?? 15 }}">
                            ⏱️ Ajouter du temps
                        </button>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>

<script>
// CORRIGÉ : Initialisation des événements améliorée
document.addEventListener('DOMContentLoaded', function() {
    console.log('📋 Initialisation des événements orders-content');
    
    // Événements pour les onglets
    document.addEventListener('click', function(e) {
        if (e.target.hasAttribute('data-status')) {
            e.preventDefault();
            const status = e.target.getAttribute('data-status');
            console.log('📁 Changement d\'onglet:', status);
            
            if (window.dashboardComponent) {
                window.dashboardComponent.loadOrders(status);
            }
        }
        
        // Événements pour les boutons "Voir Détails"
        if (e.target.classList.contains('view-order-details-btn')) {
            e.preventDefault();
            const orderId = e.target.getAttribute('data-order-id');
            openOrderDetailsModal(orderId);
        }
        
        // CORRIGÉ : Événements pour les boutons "Ajouter du Temps" - délégation d'événements
        if (e.target.classList.contains('add-time-btn')) {
            e.preventDefault();
            const orderId = e.target.getAttribute('data-order-id');
            const currentTime = e.target.getAttribute('data-current-time');
            console.log('⏱️ Clic sur bouton ajouter temps:', orderId);
            openAddTimeModal(orderId, currentTime);
        }
    });
    
    // Délégation d'événements pour les boutons "Accepter"
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('accept-order-btn')) {
            const orderId = e.target.getAttribute('data-order-id');
            console.log('🟡 Clic sur Accepter pour la commande:', orderId);
            openTimeModal(orderId);
        }
    });
});

// CORRIGÉ : Fonction pour ouvrir le modal des détails de commande
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
    
    // Charger les détails de la commande via l'API JSON
    fetch(`/admin/orders/${orderId}/ajax`)
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
                throw new Error(data.message);
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des détails:', error);
            document.getElementById('modalOrderItems').innerHTML = `
                <div class="text-center py-4 text-red-600">
                    ❌ ${error.message || 'Erreur lors du chargement des détails'}
                </div>
            `;
        });
}

// CORRIGÉ : Fonction pour remplir le modal avec les données JSON
function populateOrderModalWithJSON(orderData, orderId) {
    // Remplir les informations de base
    document.getElementById('modalOrderId').textContent = orderData.id || orderId;
    document.getElementById('modalTableNumber').textContent = orderData.table_number || 'N/A';
    document.getElementById('modalOrderType').textContent = orderData.order_type ? 
        orderData.order_type.charAt(0).toUpperCase() + orderData.order_type.slice(1) : 'N/A';
    document.getElementById('modalOrderStatus').textContent = orderData.status ? 
        orderData.status.charAt(0).toUpperCase() + orderData.status.slice(1) : 'N/A';
    document.getElementById('modalPaymentStatus').textContent = orderData.payment_status || 'N/A';
    document.getElementById('modalCustomerPhone').textContent = orderData.customer_phone || 'N/A';
    document.getElementById('modalOrderDate').textContent = orderData.created_at || 'N/A';
    document.getElementById('modalEstimatedTime').textContent = orderData.estimated_time || 'N/A';
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
                Aucun article trouvé
            </div>
        `;
    }
}

// CORRIGÉ : Fonction pour fermer le modal
function closeOrderDetailsModal() {
    document.getElementById('orderDetailsModal').style.display = 'none';
}

// Fermer le modal en cliquant à l'extérieur
document.getElementById('orderDetailsModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeOrderDetailsModal();
    }
});

// Fermer le modal avec la touche Échap
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeOrderDetailsModal();
    }
});
</script>
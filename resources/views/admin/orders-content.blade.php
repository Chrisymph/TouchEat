<div class="space-y-6" id="orders-content-container">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <h2 class="text-3xl font-bold">Gestion des Commandes</h2>
        <div class="flex space-x-4">
            <span class="bg-yellow-400 text-black-600 px-3 py-1 rounded-full text-sm font-semibold">
                {{ $orderCounts['pending'] }} en attente
            </span>
            <span class="bg-green-500 text-white px-3 py-1 rounded-full text-sm font-semibold">
                {{ $orderCounts['ready'] }} prêtes
            </span>
        </div>
    </div>

    <!-- Onglets des commandes -->
    <div class="mb-6 flex justify-center">
        <div class="bg-white shadow rounded-lg overflow-hidden w-full max-w-7xl">
            <div class="flex divide-x divide-gray-200">              
              <button data-status="pending" 
                      class="w-1/3 py-3 font-semibold text-sm transition-all duration-200
                      {{ $status === 'pending' ? 'bg-orange-100 text-orange-600' : 'bg-gray-50 text-gray-500 hover:bg-gray-100' }}">
                ⏳ En Attente ({{ $orderCounts['pending'] }})
              </button>  
              <button data-status="ready" 
                      class="w-1/3 py-3 font-semibold text-sm transition-all duration-200
                      {{ $status === 'ready' ? 'bg-orange-100 text-orange-600' : 'bg-gray-50 text-gray-500 hover:bg-gray-100' }}">
                ✅ Prêtes ({{ $orderCounts['ready'] }})
              </button>  
              <button data-status="completed" 
                      class="w-1/3 py-3 font-semibold text-sm transition-all duration-200
                      {{ $status === 'completed' ? 'bg-orange-100 text-orange-600' : 'bg-gray-50 text-gray-500 hover:bg-gray-100' }}">
                📜 Terminées ({{ $orderCounts['completed'] }})
              </button>  
            </div>
        </div>
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
            <p class="text-gray-500">Seules les commandes payées sont affichées ici</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($orders as $order)
            <div class="bg-white rounded-lg shadow transition-all duration-200 hover:shadow-lg order-card" 
                 id="order-card-{{ $order->id }}"
                 data-order-id="{{ $order->id }}"
                 data-status="{{ $order->status }}">
                <div class="p-6">
                    <!-- En-tête de la commande avec badge de statut -->
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex-1">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold">#{{ $order->id }}</h3>
                                    <p class="text-gray-600 text-sm">
                                        @if($order->order_type === 'livraison')
                                            🚚 Livraison • {{ $order->customer_phone ?? '—' }}
                                        @elseif($order->order_type === 'emporter')
                                            🛍️ À emporter • {{ $order->customer_phone ?? '—' }}
                                        @else
                                            🍽️ Table {{ $order->table_number }}
                                        @endif
                                        • {{ $order->created_at->format('H:i') }}
                                    </p>
                                    <!-- CORRECTION : Afficher le statut de paiement -->
                                    <p class="text-xs mt-1 {{ $order->payment_status === 'payé' ? 'text-green-600' : 'text-yellow-600' }}">
                                        Paiement: {{ $order->payment_status === 'payé' ? '✅ Payé' : '⏳ En attente' }}
                                    </p>
                                </div>
                                <span class="px-2 py-1 rounded-full text-xs font-semibold status-badge
                                    @if($order->status === 'commandé') bg-yellow-100 text-yellow-800
                                    @elseif($order->status === 'en_cours') bg-blue-100 text-blue-800
                                    @elseif($order->status === 'prêt') bg-green-100 text-green-800
                                    @else bg-gray-100 text-gray-800 @endif">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </div>

                            <!-- AFFICHAGE DE L'ADRESSE DE LIVRAISON -->
                            @if($order->order_type === 'livraison')
                                <div class="mt-3 p-3 bg-blue-50 rounded-lg border border-blue-200">
                                    <div class="flex items-start space-x-2">
                                        <span class="text-blue-600 mt-0.5">📍</span>
                                        <div class="flex-1">
                                            <p class="text-sm font-semibold text-blue-800">Adresse de livraison</p>
                                            @if($order->delivery_address)
                                                <p class="text-xs text-blue-700 mt-1">{{ $order->delivery_address }}</p>
                                            @else
                                                <p class="text-xs text-blue-600 italic mt-1">Adresse non spécifiée</p>
                                            @endif
                                            @if($order->delivery_notes)
                                                <p class="text-xs text-blue-600 mt-2">
                                                    <span class="font-medium">Notes:</span> {{ $order->delivery_notes }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- SECTION TIMER - AFFICHAGE DU COMPTE À REBOURS -->
                    @if($order->status === 'en_cours' && $order->estimated_time)
                    <div class="mb-4 p-4 rounded-lg border-2 timer-container 
                         @if($order->timer_almost_expired) border-orange-400 bg-orange-50 animate-pulse
                         @elseif($order->timer_expired) border-red-400 bg-red-50 animate-pulse
                         @else border-blue-200 bg-blue-50 @endif">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center space-x-2">
                                <span class="text-xl">⏱️</span>
                                <span class="font-semibold">Temps de préparation</span>
                            </div>
                            <span class="text-sm font-medium 
                                @if($order->timer_almost_expired) text-orange-700
                                @elseif($order->timer_expired) text-red-700
                                @else text-blue-700 @endif">
                                {{ $order->estimated_time }} min
                            </span>
                        </div>
                        
                        <!-- Barre de progression -->
                        <div class="w-full bg-gray-200 rounded-full h-2.5 mb-2">
                            <div class="h-2.5 rounded-full 
                                @if($order->timer_almost_expired) bg-orange-500
                                @elseif($order->timer_expired) bg-red-500
                                @else bg-blue-600 @endif"
                                 style="width: {{ min(100, $order->timer_progress_percentage) }}%">
                            </div>
                        </div>
                        
                        <!-- Informations du timer -->
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div class="text-center">
                                <div class="font-semibold 
                                    @if($order->timer_almost_expired) text-orange-700
                                    @elseif($order->timer_expired) text-red-700
                                    @else text-blue-700 @endif">
                                    {{ $order->formatted_elapsed_time }}
                                </div>
                                <div class="text-gray-600 text-xs">Écoulé</div>
                            </div>
                            <div class="text-center">
                                <div class="font-bold 
                                    @if($order->timer_almost_expired) text-orange-800
                                    @elseif($order->timer_expired) text-red-800
                                    @else text-blue-800 @endif">
                                    {{ $order->formatted_remaining_time }}
                                </div>
                                <div class="text-gray-600 text-xs">Restant</div>
                            </div>
                        </div>
                        
                        <!-- Message d'alerte -->
                        @if($order->timer_almost_expired)
                        <div class="mt-2 p-2 bg-orange-100 border border-orange-300 rounded text-center">
                            <span class="text-orange-800 font-semibold text-sm">⚠️ Bientôt terminé !</span>
                        </div>
                        @elseif($order->timer_expired)
                        <div class="mt-2 p-2 bg-red-100 border border-red-300 rounded text-center animate-pulse">
                            <span class="text-red-800 font-bold text-sm">⏰ TEMPS ÉCOULÉ !</span>
                        </div>
                        @endif
                        
                        <!-- Bouton pour ajouter du temps -->
                        @if(in_array($order->status, ['commandé', 'en_cours']) && $order->hasPreviousOrders() && $order->hasRecentAdditions())
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
                    @endif

                    <!-- Articles -->
                    <div class="space-y-2 mb-4">
                        @foreach($order->items as $item)
                        <div class="flex justify-between text-sm">
                            <span>{{ $item->menuItem->name ?? $item->product_name ?? 'Article' }} x{{ $item->quantity }}</span>
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
                            <span class="text-lg text-blue-600" id="order-{{ $order->id }}-total">
                                {{ number_format($order->total, 0, ',', ' ') }} FCFA
                            </span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex space-x-2">
                        <button type="button"
                                class="flex-1 bg-gray-100 text-gray-700 px-3 py-2 rounded text-sm hover:bg-gray-200 transition-colors view-order-details-btn"
                                data-order-id="{{ $order->id }}">
                            👁️ Voir Détails
                        </button>

                        @if($order->status === 'commandé')
                        <button type="button"
                                class="flex-1 bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700 transition-colors accept-order-btn"
                                data-order-id="{{ $order->id }}">
                            ✅ Accepter
                        </button>
                        @elseif(in_array($order->status, ['en_cours', 'prêt']))
                        <button type="button"
                                onclick="updateOrderStatus('{{ $order->id }}', '{{ $order->status === 'en_cours' ? 'prêt' : 'terminé' }}')"
                                class="flex-1 bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700 transition-colors">
                            {{ $order->status === 'en_cours' ? '🟢 Marquer Prêt' : '🏁 Terminer' }}
                        </button>
                        @endif
                    </div>

                    <!-- BOUTON IMPRIMER REÇU POUR COMMANDES TERMINÉES -->
                    @if($status === 'completed')
                    <div class="mt-3 pt-3 border-t border-gray-200">
                        <button type="button"
                                class="w-full bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700 transition-colors print-receipt-btn"
                                data-order-id="{{ $order->id }}">
                            🖨️ Imprimer Reçu
                        </button>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        
        <!-- Script pour la mise à jour en temps réel des timers -->
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Fonction pour mettre à jour les timers
            function updateTimers() {
                // Sélectionner toutes les cartes de commande avec timer actif
                const orderCards = document.querySelectorAll('.order-card[data-status="en_cours"]');
                
                orderCards.forEach(card => {
                    const orderId = card.getAttribute('data-order-id');
                    
                    // Récupérer les données du timer via AJAX
                    fetch(`/admin/orders/${orderId}/timer-data`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                updateTimerDisplay(card, data.timer_data);
                                
                                // Si le timer est presque expiré ou expiré, appliquer les animations
                                if (data.timer_data.almost_expired || data.timer_data.expired) {
                                    applyTimerAlertStyles(card, data.timer_data);
                                }
                            }
                        })
                        .catch(error => console.error('Erreur mise à jour timer:', error));
                });
            }
            
            // Fonction pour mettre à jour l'affichage du timer
            function updateTimerDisplay(card, timerData) {
                const timerContainer = card.querySelector('.timer-container');
                if (!timerContainer) return;
                
                // Mettre à jour la barre de progression
                const progressBar = timerContainer.querySelector('.h-2.5');
                if (progressBar) {
                    progressBar.style.width = `${Math.min(100, timerData.progress_percentage)}%`;
                    
                    // Changer la couleur selon l'état
                    if (timerData.expired) {
                        progressBar.className = 'h-2.5 rounded-full bg-red-500';
                    } else if (timerData.almost_expired) {
                        progressBar.className = 'h-2.5 rounded-full bg-orange-500';
                    } else {
                        progressBar.className = 'h-2.5 rounded-full bg-blue-600';
                    }
                }
                
                // Mettre à jour le temps écoulé
                const elapsedElement = timerContainer.querySelector('.text-center:first-child .font-semibold');
                if (elapsedElement && timerData.formatted_elapsed) {
                    elapsedElement.textContent = timerData.formatted_elapsed;
                    elapsedElement.className = `font-semibold ${
                        timerData.expired ? 'text-red-700' : 
                        timerData.almost_expired ? 'text-orange-700' : 
                        'text-blue-700'
                    }`;
                }
                
                // Mettre à jour le temps restant
                const remainingElement = timerContainer.querySelector('.text-center:last-child .font-bold');
                if (remainingElement && timerData.formatted_remaining) {
                    remainingElement.textContent = timerData.formatted_remaining;
                    remainingElement.className = `font-bold ${
                        timerData.expired ? 'text-red-800' : 
                        timerData.almost_expired ? 'text-orange-800' : 
                        'text-blue-800'
                    }`;
                }
                
                // Mettre à jour le message d'alerte
                const alertContainer = timerContainer.querySelector('.mt-2');
                if (alertContainer) {
                    if (timerData.expired) {
                        alertContainer.innerHTML = `
                            <div class="p-2 bg-red-100 border border-red-300 rounded text-center animate-pulse">
                                <span class="text-red-800 font-bold text-sm">⏰ TEMPS ÉCOULÉ !</span>
                            </div>
                        `;
                    } else if (timerData.almost_expired) {
                        alertContainer.innerHTML = `
                            <div class="p-2 bg-orange-100 border border-orange-300 rounded text-center">
                                <span class="text-orange-800 font-semibold text-sm">⚠️ Bientôt terminé !</span>
                            </div>
                        `;
                    } else {
                        alertContainer.innerHTML = '';
                    }
                }
                
                // Mettre à jour les classes CSS du conteneur
                timerContainer.className = `mb-4 p-4 rounded-lg border-2 timer-container ${
                    timerData.expired ? 'border-red-400 bg-red-50 animate-pulse' :
                    timerData.almost_expired ? 'border-orange-400 bg-orange-50 animate-pulse' :
                    'border-blue-200 bg-blue-50'
                }`;
                
                // Mettre à jour le badge de statut
                const statusBadge = card.querySelector('.status-badge');
                if (statusBadge) {
                    if (timerData.expired) {
                        statusBadge.className = 'px-2 py-1 rounded-full text-xs font-semibold status-badge bg-red-100 text-red-800 animate-pulse';
                    } else if (timerData.almost_expired) {
                        statusBadge.className = 'px-2 py-1 rounded-full text-xs font-semibold status-badge bg-orange-100 text-orange-800';
                    }
                }
            }
            
            // Fonction pour appliquer les styles d'alerte
            function applyTimerAlertStyles(card, timerData) {
                // Ajouter une animation de pulse à la carte
                if (timerData.expired) {
                    card.classList.add('border-2', 'border-red-400', 'animate-pulse');
                    card.style.boxShadow = '0 0 10px rgba(239, 68, 68, 0.5)';
                } else if (timerData.almost_expired) {
                    card.classList.add('border-2', 'border-orange-400', 'animate-pulse');
                    card.style.boxShadow = '0 0 10px rgba(249, 115, 22, 0.5)';
                }
                
                // Créer une notification visuelle
                if (timerData.expired) {
                    showVisualNotification(card, '⏰ Timer expiré !', 'red');
                } else if (timerData.almost_expired) {
                    showVisualNotification(card, '⚠️ Timer bientôt terminé', 'orange');
                }
            }
            
            // Fonction pour afficher une notification visuelle
            function showVisualNotification(card, message, color) {
                // Vérifier si une notification existe déjà
                let notification = card.querySelector('.timer-notification');
                
                if (!notification) {
                    notification = document.createElement('div');
                    notification.className = `timer-notification absolute top-0 left-0 right-0 p-2 text-center font-bold text-white ${
                        color === 'red' ? 'bg-red-600' : 'bg-orange-600'
                    }`;
                    notification.textContent = message;
                    
                    // Positionner la notification
                    card.style.position = 'relative';
                    card.appendChild(notification);
                    
                    // Supprimer la notification après 5 secondes
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 5000);
                }
            }
            
            // Démarrer la mise à jour périodique des timers
            updateTimers(); // Premier appel
            
            // Mettre à jour toutes les 30 secondes
            const timerInterval = setInterval(updateTimers, 30000);
            
            // Arrêter l'intervalle si on quitte la page
            window.addEventListener('beforeunload', () => {
                clearInterval(timerInterval);
            });
        });
        </script>
    @endif
</div>

<script>
// Sauvegarder le statut des commandes dans le localStorage
document.addEventListener('DOMContentLoaded', function() {
    const currentStatus = '{{ $status }}';
    localStorage.setItem('adminOrdersStatus', currentStatus);
});

// Fonction pour mettre à jour le statut via AJAX
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
            
            // Recharger seulement le contenu des commandes avec le même statut
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

// Fonction pour imprimer le reçu
async function printReceipt(orderId) {
    try {
        console.log('🖨️ Impression du reçu pour la commande:', orderId);
        
        // Ouvrir dans une nouvelle fenêtre pour impression
        const printWindow = window.open(`/admin/orders/${orderId}/print?auto_print=1`, '_blank', 'width=400,height=600');
        
        if (!printWindow) {
            throw new Error('Veuillez autoriser les pop-ups pour l\'impression');
        }
        
        showToast('🖨️ Ouverture de l\'impression...', 'success');
        
    } catch (error) {
        console.error('Erreur impression reçu:', error);
        showToast('❌ Erreur lors de l\'impression: ' + error.message, 'error');
    }
}

// MODAL POUR DÉFINIR LE TEMPS DE PRÉPARATION (NOUVEAU)
function openPreparationTimeModal(orderId) {
    document.getElementById('preparationTimeOrderId').value = orderId;
    document.getElementById('preparationTimeModal').style.display = 'flex';
    
    // Focus sur le champ de saisie
    setTimeout(() => {
        document.getElementById('preparationTimeInput').focus();
        document.getElementById('preparationTimeInput').select();
    }, 100);
}

function closePreparationTimeModal() {
    document.getElementById('preparationTimeModal').style.display = 'none';
}

// Gestion de la soumission du formulaire de temps de préparation
document.getElementById('preparationTimeForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const orderId = document.getElementById('preparationTimeOrderId').value;
    const submitButton = this.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    
    // Afficher l'indicateur de chargement
    submitButton.innerHTML = '⏳ Démarrage du timer...';
    submitButton.disabled = true;
    
    try {
        const response = await fetch(`/admin/orders/${orderId}/set-preparation-time`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            closePreparationTimeModal();
            showToast('✅ ' + result.message, 'success');
            
            // Recharger les commandes
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
    } finally {
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    }
});

// Délégation d'événements
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('click', function(e) {
        // 🔹 Changement d'onglet
        if (e.target.hasAttribute('data-status')) {
            e.preventDefault();
            const status = e.target.getAttribute('data-status');
            
            // Sauvegarder le statut
            localStorage.setItem('adminOrdersStatus', status);
            
            if (window.dashboardComponent) {
                window.dashboardComponent.loadOrders(status);
            }
        }

        // 🔹 Voir Détails
        if (e.target.classList.contains('view-order-details-btn')) {
            e.preventDefault();
            openOrderDetailsModal(e.target.getAttribute('data-order-id'));
        }

        // 🔹 Accepter Commande (OUVRE LE MODAL DE TEMPS DE PRÉPARATION)
        if (e.target.classList.contains('accept-order-btn')) {
            e.preventDefault();
            const orderId = e.target.getAttribute('data-order-id');
            openPreparationTimeModal(orderId);
        }

        // 🔹 Ajouter du Temps
        if (e.target.classList.contains('add-time-btn')) {
            e.preventDefault();
            const orderId = e.target.getAttribute('data-order-id');
            const currentTime = e.target.getAttribute('data-current-time');
            openAddTimeModal(orderId, parseInt(currentTime));
        }

        // 🔹 IMPRIMER REÇU
        if (e.target.classList.contains('print-receipt-btn')) {
            e.preventDefault();
            const orderId = e.target.getAttribute('data-order-id');
            printReceipt(orderId);
        }
    });
    
    // Fermer le modal en cliquant à l'extérieur
    document.addEventListener('click', function(e) {
        const modal = document.getElementById('preparationTimeModal');
        if (e.target === modal) {
            closePreparationTimeModal();
        }
    });
    
    // Fermer avec Échap
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closePreparationTimeModal();
        }
    });
});
</script>

<!-- MODAL POUR DÉFINIR LE TEMPS DE PRÉPARATION (NOUVEAU) -->
<div id="preparationTimeModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" style="display: none;">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
        <div class="p-6">
            <h3 class="text-lg font-semibold mb-4">Définir le temps de préparation</h3>
            
            <form id="preparationTimeForm" method="POST">
                @csrf
                <input type="hidden" name="status" value="en_cours">
                <input type="hidden" id="preparationTimeOrderId" name="order_id">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Temps de préparation estimé (minutes)
                        </label>
                        <input type="number" name="preparation_time" id="preparationTimeInput" 
                               required min="1" max="120" value="15"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="15">
                        <p class="text-sm text-gray-500 mt-1">Temps estimé pour préparer la commande</p>
                    </div>
                    
                    <!-- Suggestions de temps -->
                    <div class="grid grid-cols-4 gap-2">
                        <button type="button" onclick="document.getElementById('preparationTimeInput').value = '5'" 
                                class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-sm transition-colors">
                            5 min
                        </button>
                        <button type="button" onclick="document.getElementById('preparationTimeInput').value = '10'" 
                                class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-sm transition-colors">
                            10 min
                        </button>
                        <button type="button" onclick="document.getElementById('preparationTimeInput').value = '15'" 
                                class="px-3 py-2 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded text-sm transition-colors">
                            15 min
                        </button>
                        <button type="button" onclick="document.getElementById('preparationTimeInput').value = '20'" 
                                class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-sm transition-colors">
                            20 min
                        </button>
                    </div>
                    
                    <div class="bg-blue-50 p-3 rounded-lg">
                        <p class="text-sm text-blue-700">
                            ⏱️ Ce temps sera affiché au client avec un compte à rebours.
                        </p>
                        <p class="text-xs text-blue-600 mt-1">
                            Une notification visuelle s'affichera quand le temps sera presque écoulé.
                        </p>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closePreparationTimeModal()" 
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

<style>
/* Styles pour les notifications visuelles */
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

/* Animation de pulse pour les timers presque expirés */
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

/* Styles pour les barres de progression */
.bg-blue-600 {
    transition: width 1s ease-in-out;
}

.bg-orange-500 {
    transition: width 1s ease-in-out;
}

.bg-red-500 {
    transition: width 1s ease-in-out;
}
</style>
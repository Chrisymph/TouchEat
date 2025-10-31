<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commande Confirmée - Table {{ $tableNumber }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .timer-circle {
            width: 120px;
            height: 120px;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="gradient-bg text-white">
        <div class="container mx-auto px-4 py-6">
            <div class="flex justify-between items-center">
                <div class="text-center flex-1">
                    <h1 class="text-3xl font-bold mb-2">Commande Confirmée</h1>
                    <div class="flex justify-center items-center space-x-4 text-lg">
                        <span>Table N°{{ $tableNumber }}</span>
                        <span class="text-white/70">•</span>
                        <span class="text-white/80">Votre commande est en préparation</span>
                    </div>
                </div>
                <form action="{{ route('client.logout') }}" method="POST">
                    @csrf
                    <button type="submit" 
                            class="bg-white/20 hover:bg-white/30 text-white px-6 py-2 rounded-lg font-semibold transition-colors backdrop-blur-sm">
                        Déconnexion
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8" x-data="orderConfirmation()">
        <div class="max-w-6xl mx-auto">
            <!-- En-tête avec timer -->
            <div class="text-center mb-8">
                <div class="flex justify-center items-center space-x-6 mb-6">
                    <!-- Timer circulaire -->
                    <div class="relative timer-circle">
                        <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                            <!-- Cercle de fond -->
                            <circle cx="50" cy="50" r="45" fill="none" stroke="#e5e7eb" stroke-width="8"/>
                            <!-- Cercle de progression - SEULEMENT si le timer est actif -->
                            <circle x-show="showTimerProgress" cx="50" cy="50" r="45" fill="none" 
                                    stroke="#3b82f6" stroke-width="8" stroke-linecap="round"
                                    :stroke-dasharray="283"
                                    :stroke-dashoffset="283 - (283 * (elapsedTime / estimatedTime))"
                                    class="transition-all duration-1000"/>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <template x-if="showTimer">
                                <span x-text="remainingMinutes" class="text-3xl font-bold text-gray-800"></span>
                            </template>
                            <template x-if="!showTimer">
                                <span class="text-3xl font-bold text-gray-800">✓</span>
                            </template>
                            <span class="text-sm text-gray-600" x-text="timerLabel"></span>
                        </div>
                    </div>
                    
                    <!-- Statut de la commande -->
                    <div class="text-left">
                        <h2 class="text-2xl font-bold text-gray-800 mb-2" x-text="statusText"></h2>
                        <p class="text-gray-600" x-text="statusDescription"></p>
                        <div class="mt-4 space-y-2">
                            <div class="flex items-center space-x-2">
                                <div :class="getStatusColor('commandé')" class="w-3 h-3 rounded-full"></div>
                                <span class="text-sm" :class="getStatusTextColor('commandé')">
                                    Commande reçue
                                </span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div :class="getStatusColor('en_cours')" class="w-3 h-3 rounded-full"></div>
                                <span class="text-sm" :class="getStatusTextColor('en_cours')">
                                    En préparation
                                </span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div :class="getStatusColor('prêt')" class="w-3 h-3 rounded-full"></div>
                                <span class="text-sm" :class="getStatusTextColor('prêt')">
                                    Prêt à servir
                                </span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div :class="getStatusColor('terminé')" class="w-3 h-3 rounded-full"></div>
                                <span class="text-sm" :class="getStatusTextColor('terminé')">
                                    Terminé
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Détails de la commande -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                        <h3 class="text-xl font-bold text-gray-800 mb-4">Détails de votre commande</h3>
                        
                        <div class="space-y-4">
                            <template x-for="item in orderItems" :key="item.id">
                                <div class="flex justify-between items-center py-3 border-b border-gray-100">
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-gray-800" x-text="item.menu_item.name"></h4>
                                        <p class="text-sm text-gray-600" x-text="item.menu_item.description"></p>
                                    </div>
                                    <div class="text-right">
                                        <div class="flex items-center space-x-4">
                                            <span class="text-gray-800 font-semibold" x-text="`x${item.quantity}`"></span>
                                            <span class="font-bold text-gray-800" 
                                                  x-text="formatPrice(item.unit_price * item.quantity)"></span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Actions supplémentaires -->
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                        <h3 class="text-xl font-bold text-gray-800 mb-4">Actions</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <button @click="requestDelivery()" 
                                    :disabled="orderStatus === 'terminé' || orderStatus === 'prêt'"
                                    :class="(orderStatus === 'terminé' || orderStatus === 'prêt') ? 'bg-gray-400 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700'"
                                    class="w-full text-white py-4 rounded-lg font-semibold transition-all duration-300 shadow-md hover:shadow-lg">
                                🚗 Demander la livraison
                            </button>
                            <!-- CORRIGÉ : Bouton désactivé quand la commande est prête -->
                            <button @click="addToMenu()" 
                                    :disabled="orderStatus === 'terminé' || orderStatus === 'prêt'"
                                    :class="(orderStatus === 'terminé' || orderStatus === 'prêt') ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'"
                                    class="w-full text-white py-4 rounded-lg font-semibold transition-all duration-300 shadow-md hover:shadow-lg">
                                📋 Ajouter au menu
                            </button>
                        </div>
                        <!-- CORRIGÉ : Message d'information quand le bouton est désactivé -->
                        <template x-if="orderStatus === 'prêt'">
                            <p class="text-sm text-gray-500 mt-3 text-center">
                                ⚠️ Impossible d'ajouter des articles - La commande est prête à être servie
                            </p>
                        </template>
                        <template x-if="orderStatus === 'terminé'">
                            <p class="text-sm text-gray-500 mt-3 text-center">
                                ✅ Commande terminée - Merci pour votre visite !
                            </p>
                        </template>
                    </div>
                </div>

                <!-- Récapitulatif -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 sticky top-6">
                        <h3 class="text-xl font-bold text-gray-800 mb-4">Récapitulatif</h3>
                        
                        <div class="space-y-3 mb-4">
                            <template x-for="item in orderItems" :key="item.id">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600" x-text="`${item.menu_item.name} x${item.quantity}`"></span>
                                    <span class="font-semibold" 
                                          x-text="formatPrice(item.unit_price * item.quantity)"></span>
                                </div>
                            </template>
                        </div>
                        
                        <div class="border-t pt-4 mb-6">
                            <div class="flex justify-between items-center text-lg font-bold text-gray-800">
                                <span>Total</span>
                                <span x-text="formatPrice(orderTotal)"></span>
                            </div>
                        </div>

                        <!-- Informations de la commande -->
                        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                            <h4 class="font-semibold text-blue-800 mb-2">Informations</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-blue-700">N° Commande:</span>
                                    <span class="font-semibold" x-text="orderId"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-blue-700">Table:</span>
                                    <span class="font-semibold" x-text="tableNumber"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-blue-700">Heure:</span>
                                    <span class="font-semibold" x-text="orderTime"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-blue-700">Type:</span>
                                    <span class="font-semibold" x-text="orderType === 'sur_place' ? 'Sur place' : 'Livraison'"></span>
                                </div>
                                <template x-if="showTimer">
                                    <div class="flex justify-between">
                                        <span class="text-blue-700">Temps estimé:</span>
                                        <span class="font-semibold" x-text="estimatedTime + ' min'"></span>
                                    </div>
                                </template>
                                <template x-if="!showTimer && orderStatus === 'prêt'">
                                    <div class="flex justify-between">
                                        <span class="text-blue-700">Statut:</span>
                                        <span class="font-semibold text-green-600">Prêt à être servi !</span>
                                    </div>
                                </template>
                                <template x-if="orderStatus === 'terminé'">
                                    <div class="flex justify-between">
                                        <span class="text-blue-700">Statut:</span>
                                        <span class="font-semibold text-gray-600">Commande terminée</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bouton retour -->
            <div class="text-center mt-8">
                <button onclick="window.location.href = '/client/dashboard'" 
                        class="bg-gray-500 hover:bg-gray-600 text-white px-8 py-3 rounded-lg font-semibold transition-all duration-300 shadow-md hover:shadow-lg">
                    ← Retour à l'accueil
                </button>
            </div>
        </div>
    </div>

    <script>
        function orderConfirmation() {
            return {
                orderId: {{ $order->id }},
                tableNumber: {{ $tableNumber }},
                orderItems: @json($order->items->load('menuItem')),
                orderTotal: {{ $order->total }},
                orderType: '{{ $order->order_type }}',
                orderTime: '{{ $order->created_at->format('H:i') }}',
                
                // Timer
                estimatedTime: {{ $order->estimated_time ?? 0 }},
                elapsedTime: 0,
                timerInterval: null,
                
                // Statut
                orderStatus: '{{ $order->status }}',
                statusCheckInterval: null,

                init() {
                    this.startStatusCheck();
                    // Démarrer le timer seulement si la commande est en cours et a un temps estimé
                    if (this.orderStatus === 'en_cours' && this.estimatedTime > 0) {
                        this.startTimer();
                    }
                },

                get showTimer() {
                    // Afficher le timer seulement si la commande est en cours de préparation
                    return this.orderStatus === 'en_cours' && this.estimatedTime > 0;
                },

                get showTimerProgress() {
                    // Afficher la progression seulement si le timer est actif et n'est pas terminé
                    return this.showTimer && this.elapsedTime < this.estimatedTime;
                },

                get timerLabel() {
                    if (this.orderStatus === 'terminé') return 'Terminé';
                    if (this.orderStatus === 'prêt') return 'Prêt !';
                    if (this.showTimer) return 'min';
                    return 'En attente';
                },

                get remainingMinutes() {
                    const remaining = Math.max(0, this.estimatedTime - this.elapsedTime);
                    return Math.ceil(remaining);
                },

                get statusText() {
                    const statusMap = {
                        'commandé': 'Commande en attente',
                        'en_cours': 'En préparation',
                        'prêt': 'Prêt à servir !',
                        'terminé': 'Commande terminée'
                    };
                    return statusMap[this.orderStatus] || 'Statut inconnu';
                },

                get statusDescription() {
                    const descMap = {
                        'commandé': 'Votre commande a été reçue et sera bientôt prise en charge',
                        'en_cours': 'Notre équipe prépare votre commande avec soin',
                        'prêt': 'Votre commande est prête ! Vous pouvez venir la récupérer',
                        'terminé': 'Merci pour votre commande ! À bientôt'
                    };
                    return descMap[this.orderStatus] || '';
                },

                getStatusColor(status) {
                    if (status === this.orderStatus) {
                        return 'bg-yellow-500';
                    }
                    const statusOrder = ['commandé', 'en_cours', 'prêt', 'terminé'];
                    const currentIndex = statusOrder.indexOf(this.orderStatus);
                    const targetIndex = statusOrder.indexOf(status);
                    
                    if (targetIndex < currentIndex) return 'bg-green-500';
                    if (targetIndex === currentIndex) return 'bg-yellow-500';
                    return 'bg-gray-300';
                },

                getStatusTextColor(status) {
                    if (status === this.orderStatus) {
                        return 'text-yellow-600 font-semibold';
                    }
                    const statusOrder = ['commandé', 'en_cours', 'prêt', 'terminé'];
                    const currentIndex = statusOrder.indexOf(this.orderStatus);
                    const targetIndex = statusOrder.indexOf(status);
                    
                    if (targetIndex < currentIndex) return 'text-green-600';
                    if (targetIndex === currentIndex) return 'text-yellow-600 font-semibold';
                    return 'text-gray-400';
                },

                // CORRIGÉ : Rediriger vers le menu avec l'ID de commande - Désactivé quand prêt
                addToMenu() {
                    if (this.orderStatus === 'prêt' || this.orderStatus === 'terminé') {
                        this.showToast('Impossible d\'ajouter des articles à une commande prête ou terminée', 'error');
                        return;
                    }
                    
                    // Stocker l'ID de commande dans le localStorage pour le récupérer dans le dashboard
                    localStorage.setItem('currentOrderId', this.orderId);
                    localStorage.setItem('addingToExistingOrder', 'true');
                    
                    // Rediriger vers le dashboard
                    window.location.href = '/client/dashboard?order_id=' + this.orderId;
                },

                startTimer() {
                    // Ne démarrer le timer que si la commande est en cours et a un temps estimé
                    if (this.orderStatus !== 'en_cours' || this.estimatedTime <= 0) {
                        return;
                    }
                    
                    console.log('⏰ Démarrage du timer:', this.estimatedTime + ' minutes');
                    
                    this.timerInterval = setInterval(() => {
                        if (this.elapsedTime < this.estimatedTime) {
                            this.elapsedTime += 1/60; // Incrémenter chaque seconde
                        } else {
                            // Timer terminé mais la commande est toujours en cours
                            console.log('⏰ Timer terminé');
                            clearInterval(this.timerInterval);
                        }
                    }, 1000);
                },

                stopTimer() {
                    if (this.timerInterval) {
                        clearInterval(this.timerInterval);
                        this.timerInterval = null;
                        console.log('⏰ Timer arrêté');
                    }
                },

                startStatusCheck() {
                    this.statusCheckInterval = setInterval(async () => {
                        try {
                            const response = await fetch(`/client/order/${this.orderId}/status`);
                            const data = await response.json();
                            
                            if (data.status !== this.orderStatus) {
                                console.log('🔄 Changement de statut:', this.orderStatus, '→', data.status);
                                const oldStatus = this.orderStatus;
                                this.orderStatus = data.status;
                                
                                // Gestion du timer selon le nouveau statut
                                if (this.orderStatus === 'en_cours' && data.estimated_time && data.estimated_time > 0) {
                                    // Commande mise en préparation avec nouveau temps estimé
                                    this.estimatedTime = data.estimated_time;
                                    this.elapsedTime = 0;
                                    this.stopTimer();
                                    this.startTimer();
                                } 
                                else if (this.orderStatus === 'prêt') {
                                    // Commande prête - arrêter le timer
                                    this.stopTimer();
                                }
                                else if (this.orderStatus === 'terminé') {
                                    // Commande terminée - arrêter tout
                                    this.stopTimer();
                                    clearInterval(this.statusCheckInterval);
                                }
                                else if (oldStatus === 'en_cours' && this.orderStatus !== 'en_cours') {
                                    // Si on quitte le statut "en_cours", arrêter le timer
                                    this.stopTimer();
                                }
                            }
                        } catch (error) {
                            console.error('Erreur de vérification du statut:', error);
                        }
                    }, 5000); // Vérifier toutes les 5 secondes
                },

                formatPrice(price) {
                    return new Intl.NumberFormat('fr-FR', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0
                    }).format(price) + ' FCFA';
                },

                requestDelivery() {
                    if (this.orderStatus === 'prêt' || this.orderStatus === 'terminé') {
                        this.showToast('Impossible de demander la livraison pour une commande prête ou terminée', 'error');
                        return;
                    }
                    
                    if (confirm('Souhaitez-vous que nous vous apportions votre commande à table ?')) {
                        // Logique pour demander la livraison
                        this.showToast('Service en table demandé !');
                    }
                },

                showToast(message, type = 'success') {
                    const toast = document.createElement('div');
                    toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white font-semibold z-50 ${
                        type === 'success' ? 'bg-green-500' : 'bg-red-500'
                    }`;
                    toast.textContent = message;
                    document.body.appendChild(toast);

                    setTimeout(() => {
                        toast.remove();
                    }, 3000);
                },

                destroy() {
                    this.stopTimer();
                    if (this.statusCheckInterval) clearInterval(this.statusCheckInterval);
                }
            }
        }
    </script>
</body>
</html>
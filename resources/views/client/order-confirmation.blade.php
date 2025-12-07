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
        .timer-progress {
            transition: stroke-dashoffset 1s linear;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        .status-commandé { background-color: #fbbf24; color: #78350f; }
        .status-en_cours { background-color: #3b82f6; color: white; }
        .status-prêt { background-color: #10b981; color: white; }
        .status-terminé { background-color: #6b7280; color: white; }
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
    <div class="container mx-auto px-4 py-8" x-data="orderConfirmation()" x-init="init()">
        <div class="max-w-6xl mx-auto">
            <!-- En-tête avec timer -->
            <div class="text-center mb-8">
                <div class="flex justify-center items-center space-x-6 mb-6">
                    <!-- Timer circulaire -->
                    <div class="relative timer-circle">
                        <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                            <!-- Cercle de fond -->
                            <circle cx="50" cy="50" r="45" fill="none" stroke="#e5e7eb" stroke-width="8"/>
                            <!-- Cercle de progression -->
                            <template x-if="showTimer && totalSeconds > 0">
                                <circle cx="50" cy="50" r="45" fill="none" 
                                        stroke="#3b82f6" stroke-width="8" stroke-linecap="round"
                                        :stroke-dasharray="283"
                                        :stroke-dashoffset="283 - (283 * (elapsedSeconds / totalSeconds))"
                                        class="timer-progress"/>
                            </template>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <template x-if="showTimer && totalSeconds > 0">
                                <div class="text-center">
                                    <span x-text="remainingMinutes" class="text-3xl font-bold text-gray-800"></span>
                                    <span class="block text-sm text-gray-600">min restantes</span>
                                </div>
                            </template>
                            <template x-if="orderStatus === 'prêt'">
                                <div class="text-center">
                                    <span class="text-3xl font-bold text-green-600">✓</span>
                                    <span class="block text-sm text-green-600">Prêt !</span>
                                </div>
                            </template>
                            <template x-if="orderStatus === 'terminé'">
                                <div class="text-center">
                                    <span class="text-3xl font-bold text-gray-600">✓</span>
                                    <span class="block text-sm text-gray-600">Terminé</span>
                                </div>
                            </template>
                            <template x-if="orderStatus === 'commandé'">
                                <div class="text-center">
                                    <span class="text-3xl font-bold text-yellow-600">⏳</span>
                                    <span class="block text-sm text-yellow-600">En attente</span>
                                </div>
                            </template>
                            <template x-if="orderStatus === 'en_cours' && !showTimer">
                                <div class="text-center">
                                    <span class="text-3xl font-bold text-blue-600">🔄</span>
                                    <span class="block text-sm text-blue-600">En préparation</span>
                                </div>
                            </template>
                        </div>
                    </div>
                    
                    <!-- Statut de la commande -->
                    <div class="text-left">
                        <div class="flex items-center space-x-3 mb-3">
                            <h2 class="text-2xl font-bold text-gray-800" x-text="statusText"></h2>
                            <span :class="'status-badge status-' + orderStatus" x-text="getStatusBadgeText()"></span>
                        </div>
                        <p class="text-gray-600 mb-4" x-text="statusDescription"></p>
                        
                        <!-- Informations de temps -->
                        <div class="space-y-3 text-sm text-gray-600">
                            <template x-if="showTimer && totalSeconds > 0">
                                <div class="flex items-center space-x-2">
                                    <span class="font-semibold">Temps écoulé:</span>
                                    <span x-text="formatTime(elapsedSeconds)"></span>
                                </div>
                            </template>
                            <template x-if="showTimer && totalSeconds > 0">
                                <div class="flex items-center space-x-2">
                                    <span class="font-semibold">Temps estimé total:</span>
                                    <span x-text="estimatedTime + ' minutes'"></span>
                                </div>
                            </template>
                            <template x-if="showTimer && startedAt && startedAt !== 'null'">
                                <div class="flex items-center space-x-2">
                                    <span class="font-semibold">Début de préparation:</span>
                                    <span x-text="startedAtFormatted"></span>
                                </div>
                            </template>
                        </div>

                        <!-- Timeline de statut -->
                        <div class="mt-6">
                            <div class="flex items-center space-x-2 mb-4">
                                <div class="h-1 flex-1 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-blue-500 transition-all duration-500" 
                                         :style="'width: ' + getStatusProgress() + '%'"></div>
                                </div>
                                <span class="text-xs text-gray-500" x-text="getStatusProgress() + '%'"></span>
                            </div>
                            <div class="flex justify-between text-xs text-gray-600">
                                <span :class="orderStatus === 'commandé' ? 'font-semibold text-blue-600' : ''">Commandé</span>
                                <span :class="orderStatus === 'en_cours' ? 'font-semibold text-blue-600' : ''">En cours</span>
                                <span :class="orderStatus === 'prêt' ? 'font-semibold text-green-600' : ''">Prêt</span>
                                <span :class="orderStatus === 'terminé' ? 'font-semibold text-gray-600' : ''">Terminé</span>
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
                            @foreach($order->items as $item)
                            <div class="flex justify-between items-center py-3 border-b border-gray-100">
                                <div class="flex-1">
                                    <h4 class="font-semibold text-gray-800">{{ $item->menuItem->name ?? 'Article inconnu' }}</h4>
                                    <p class="text-sm text-gray-600">{{ $item->menuItem->description ?? '' }}</p>
                                </div>
                                <div class="text-right">
                                    <div class="flex items-center space-x-4">
                                        <span class="text-gray-800 font-semibold">x{{ $item->quantity }}</span>
                                        <span class="font-bold text-gray-800">
                                            {{ number_format($item->unit_price * $item->quantity, 0, ',', ' ') }} FCFA
                                        </span>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Actions supplémentaires -->
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                        <h3 class="text-xl font-bold text-gray-800 mb-4">Actions</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <button @click="showDeliveryModal = true" 
                                    :disabled="orderStatus === 'terminé' || orderStatus === 'prêt'"
                                    :class="(orderStatus === 'terminé' || orderStatus === 'prêt') ? 'bg-gray-400 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700'"
                                    class="w-full text-white py-4 rounded-lg font-semibold transition-all duration-300 shadow-md hover:shadow-lg">
                                🚗 Demander la livraison
                            </button>
                            <button @click="addToMenu()" 
                                    :disabled="orderStatus === 'terminé' || orderStatus === 'prêt'"
                                    :class="(orderStatus === 'terminé' || orderStatus === 'prêt') ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'"
                                    class="w-full text-white py-4 rounded-lg font-semibold transition-all duration-300 shadow-md hover:shadow-lg">
                                📋 Ajouter au menu
                            </button>
                        </div>
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
                            @foreach($order->items as $item)
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">{{ $item->menuItem->name ?? 'Article inconnu' }} x{{ $item->quantity }}</span>
                                <span class="font-semibold">
                                    {{ number_format($item->unit_price * $item->quantity, 0, ',', ' ') }} FCFA
                                </span>
                            </div>
                            @endforeach
                        </div>
                        
                        <div class="border-t pt-4 mb-6">
                            <div class="flex justify-between items-center text-lg font-bold text-gray-800">
                                <span>Total</span>
                                <span>{{ number_format($order->total, 0, ',', ' ') }} FCFA</span>
                            </div>
                        </div>

                        <!-- Informations de la commande -->
                        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                            <h4 class="font-semibold text-blue-800 mb-2">Informations</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-blue-700">N° Commande:</span>
                                    <span class="font-semibold">{{ $order->id }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-blue-700">Table:</span>
                                    <span class="font-semibold">{{ $tableNumber }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-blue-700">Heure:</span>
                                    <span class="font-semibold">{{ $order->created_at->format('H:i') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-blue-700">Type:</span>
                                    <span class="font-semibold">
                                        {{ $order->order_type === 'sur_place' ? 'Sur place' : 'Livraison' }}
                                    </span>
                                </div>
                                <template x-if="showTimer && totalSeconds > 0">
                                    <div class="flex justify-between">
                                        <span class="text-blue-700">Temps estimé:</span>
                                        <span class="font-semibold" x-text="estimatedTime + ' min'"></span>
                                    </div>
                                </template>
                                <template x-if="showTimer && totalSeconds > 0">
                                    <div class="flex justify-between">
                                        <span class="text-blue-700">Temps écoulé:</span>
                                        <span class="font-semibold" x-text="formatTime(elapsedSeconds)"></span>
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
                <a href="/client/dashboard" 
                   class="inline-block bg-gray-500 hover:bg-gray-600 text-white px-8 py-3 rounded-lg font-semibold transition-all duration-300 shadow-md hover:shadow-lg">
                    ← Retour à l'accueil
                </a>
            </div>
        </div>

        <!-- Modal de livraison -->
        <template x-if="showDeliveryModal">
            <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-2xl p-6 max-w-md w-full shadow-xl">
                    <h3 class="text-2xl font-bold text-gray-800 mb-2">Demande de Livraison</h3>
                    <p class="text-gray-600 mb-6">Veuillez fournir les informations pour la livraison</p>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Adresse de livraison <span class="text-red-500">*</span>
                            </label>
                            <input type="text" x-model="deliveryAddress" 
                                   placeholder="Ex: Table 5, Zone VIP, Terrasse..."
                                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                   required>
                            <p class="text-xs text-gray-500 mt-1">Indiquez l'emplacement exact où nous devons vous livrer</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Notes supplémentaires (optionnel)</label>
                            <textarea x-model="deliveryNotes" 
                                      placeholder="Instructions spéciales, préférences, etc."
                                      rows="3"
                                      class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"></textarea>
                        </div>
                        
                        <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                            <div class="flex items-center space-x-2 text-green-800 mb-2">
                                <span class="text-lg">🚗</span>
                                <span class="font-semibold">Service de livraison</span>
                            </div>
                            <p class="text-sm text-green-700">
                                Notre équipe vous apportera votre commande directement à votre emplacement.
                                Temps de livraison estimé : 5-10 minutes après préparation.
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex gap-3 mt-6">
                        <button @click="showDeliveryModal = false; deliveryAddress = ''; deliveryNotes = ''" 
                                class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-3 rounded-lg font-semibold transition-all duration-300">
                            Annuler
                        </button>
                        <button @click="requestDelivery()" 
                                :disabled="!deliveryAddress.trim() || isProcessingDelivery"
                                :class="!deliveryAddress.trim() || isProcessingDelivery ? 'bg-green-400 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700'"
                                class="flex-1 text-white py-3 rounded-lg font-semibold transition-all duration-300">
                            <template x-if="isProcessingDelivery">
                                <span class="flex items-center justify-center space-x-2">
                                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>Traitement...</span>
                                </span>
                            </template>
                            <template x-if="!isProcessingDelivery">
                                Confirmer la livraison
                            </template>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <script>
        function orderConfirmation() {
            return {
                orderId: {{ $order->id }},
                tableNumber: {{ $tableNumber }},
                orderTotal: {{ $order->total }},
                orderType: '{{ $order->order_type }}',
                orderTime: '{{ $order->created_at->format('H:i') }}',
                
                // Timer
                estimatedTime: {{ $order->estimated_time ?? 0 }},
                startedAt: @if($startedAt) '{{ $startedAt }}' @else null @endif,
                initialElapsedSeconds: {{ $elapsedMinutes * 60 }}, // Convertir en secondes
                elapsedSeconds: {{ $elapsedMinutes * 60 }},
                totalSeconds: 0,
                remainingTime: 0,
                timerInterval: null,
                
                // Statut
                orderStatus: '{{ $order->status }}',
                statusCheckInterval: null,

                // Livraison
                showDeliveryModal: false,
                deliveryAddress: '',
                deliveryNotes: '',
                isProcessingDelivery: false,

                init() {
                    console.log('🔄 Initialisation de la confirmation de commande');
                    console.log('- Statut:', this.orderStatus);
                    console.log('- Temps estimé:', this.estimatedTime);
                    console.log('- Temps écoulé initial:', this.initialElapsedSeconds, 'secondes');
                    console.log('- Début de préparation:', this.startedAt);
                    console.log('- StartedAt type:', typeof this.startedAt);
                    
                    this.startStatusCheck();
                    
                    // Initialiser le timer si la commande est en cours
                    if (this.orderStatus === 'en_cours' && this.estimatedTime > 0) {
                        this.initializeTimer();
                        this.startTimer();
                    }
                },

                get showTimer() {
                    return this.orderStatus === 'en_cours' && this.estimatedTime > 0;
                },

                get remainingMinutes() {
                    const remaining = this.remainingTime > 0 ? Math.ceil(this.remainingTime / 60) : 0;
                    return Math.max(0, remaining);
                },

                get startedAtFormatted() {
                    if (!this.startedAt || this.startedAt === 'null' || this.startedAt === null) {
                        return 'Non démarré';
                    }
                    try {
                        const date = new Date(this.startedAt);
                        return date.toLocaleTimeString('fr-FR', { 
                            hour: '2-digit', 
                            minute: '2-digit' 
                        }) + ' ' + date.toLocaleDateString('fr-FR');
                    } catch (e) {
                        console.error('Erreur formatage date:', e);
                        return 'Non démarré';
                    }
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

                getStatusBadgeText() {
                    const badgeMap = {
                        'commandé': 'En attente',
                        'en_cours': 'En cours',
                        'prêt': 'Prêt',
                        'terminé': 'Terminé'
                    };
                    return badgeMap[this.orderStatus] || this.orderStatus;
                },

                getStatusProgress() {
                    const progressMap = {
                        'commandé': 25,
                        'en_cours': 50,
                        'prêt': 75,
                        'terminé': 100
                    };
                    return progressMap[this.orderStatus] || 0;
                },

                initializeTimer() {
                    // Convertir le temps estimé en secondes
                    this.totalSeconds = this.estimatedTime * 60;
                    
                    // Initialiser le temps écoulé à partir de la valeur initiale
                    this.elapsedSeconds = this.initialElapsedSeconds;
                    
                    // Calculer le temps restant
                    this.remainingTime = Math.max(0, this.totalSeconds - this.elapsedSeconds);
                    
                    console.log('⏰ Timer initialisé:');
                    console.log('- Total:', this.totalSeconds, 'secondes');
                    console.log('- Écoulé initial:', this.elapsedSeconds, 'secondes');
                    console.log('- Restant:', this.remainingTime, 'secondes');
                    
                    // Si le timer est déjà terminé mais la commande est toujours en cours, 
                    // on affiche 0 minutes restantes mais on garde le timer actif
                    if (this.elapsedSeconds >= this.totalSeconds) {
                        console.log('⏰ Timer déjà terminé, affichage 0 min restantes');
                        this.elapsedSeconds = this.totalSeconds;
                        this.remainingTime = 0;
                    }
                },

                startTimer() {
                    if (!this.showTimer || this.timerInterval) {
                        return;
                    }
                    
                    console.log('⏰ Démarrage du timer...');
                    
                    this.timerInterval = setInterval(() => {
                        if (this.elapsedSeconds < this.totalSeconds) {
                            this.elapsedSeconds += 1;
                            this.remainingTime = Math.max(0, this.totalSeconds - this.elapsedSeconds);
                        } else {
                            // Timer terminé mais la commande est toujours en cours
                            console.log('⏰ Timer terminé');
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

                async startStatusCheck() {
                    this.statusCheckInterval = setInterval(async () => {
                        try {
                            const response = await fetch(`/client/order/${this.orderId}/status`);
                            const data = await response.json();
                            
                            if (data.error) {
                                console.error('Erreur dans la réponse:', data.error);
                                return;
                            }
                            
                            // Vérifier les changements
                            const hasStatusChanged = data.status !== this.orderStatus;
                            const hasTimeChanged = data.estimated_time !== this.estimatedTime;
                            const hasStartedAtChanged = data.started_at !== this.startedAt;
                            const hasElapsedChanged = data.elapsed_minutes !== Math.floor(this.elapsedSeconds / 60);
                            
                            if (hasStatusChanged || hasTimeChanged || hasStartedAtChanged || hasElapsedChanged) {
                                console.log('🔄 Mise à jour du statut:', {
                                    'ancien_statut': this.orderStatus,
                                    'nouveau_statut': data.status,
                                    'ancien_temps': this.estimatedTime,
                                    'nouveau_temps': data.estimated_time,
                                    'ancien_started_at': this.startedAt,
                                    'nouveau_started_at': data.started_at
                                });
                                
                                // Sauvegarder l'ancien statut
                                const oldStatus = this.orderStatus;
                                
                                // Mettre à jour les propriétés
                                this.orderStatus = data.status;
                                this.estimatedTime = data.estimated_time;
                                this.startedAt = data.started_at;
                                
                                // Mettre à jour le temps écoulé depuis le serveur
                                if (data.elapsed_minutes !== undefined) {
                                    this.initialElapsedSeconds = data.elapsed_minutes * 60;
                                    this.elapsedSeconds = this.initialElapsedSeconds;
                                }
                                
                                // Gestion du timer
                                if (this.orderStatus === 'en_cours' && this.estimatedTime > 0) {
                                    // Si la commande est mise en cours et a un temps estimé
                                    this.stopTimer();
                                    this.initializeTimer();
                                    this.startTimer();
                                } 
                                else if (oldStatus === 'en_cours' && this.orderStatus !== 'en_cours') {
                                    // Si on quitte le statut "en_cours", arrêter le timer
                                    this.stopTimer();
                                }
                                else if (this.orderStatus === 'prêt' || this.orderStatus === 'terminé') {
                                    // Commande prête ou terminée - arrêter le timer
                                    this.stopTimer();
                                }
                                else if (oldStatus !== 'en_cours' && this.orderStatus === 'en_cours' && this.estimatedTime > 0) {
                                    // Si on passe à "en_cours" pour la première fois
                                    this.stopTimer();
                                    this.initializeTimer();
                                    this.startTimer();
                                }
                            }
                        } catch (error) {
                            console.error('Erreur de vérification du statut:', error);
                        }
                    }, 5000); // Vérifier toutes les 5 secondes
                },

                addToMenu() {
                    if (this.orderStatus === 'prêt' || this.orderStatus === 'terminé') {
                        this.showToast('Impossible d\'ajouter des articles à une commande prête ou terminée', 'error');
                        return;
                    }
                    
                    localStorage.setItem('currentOrderId', this.orderId);
                    localStorage.setItem('addingToExistingOrder', 'true');
                    
                    window.location.href = '/client/dashboard?order_id=' + this.orderId;
                },

                async requestDelivery() {
                    if (!this.deliveryAddress.trim()) {
                        this.showToast('Veuillez saisir une adresse de livraison', 'error');
                        return;
                    }

                    this.isProcessingDelivery = true;

                    try {
                        const response = await fetch(`/client/order/${this.orderId}/request-delivery`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                delivery_address: this.deliveryAddress,
                                delivery_notes: this.deliveryNotes
                            })
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            this.showToast(data.message);
                            this.orderType = data.order_type;
                            this.showDeliveryModal = false;
                            this.deliveryAddress = '';
                            this.deliveryNotes = '';
                            
                            setTimeout(() => {
                                location.reload();
                            }, 2000);
                        } else {
                            this.showToast(data.message || 'Erreur lors de la demande de livraison', 'error');
                        }
                    } catch (error) {
                        console.error('Erreur:', error);
                        this.showToast('Erreur lors de la demande de livraison', 'error');
                    } finally {
                        this.isProcessingDelivery = false;
                    }
                },

                formatTime(seconds) {
                    const minutes = Math.floor(seconds / 60);
                    const remainingSeconds = Math.floor(seconds % 60);
                    return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
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
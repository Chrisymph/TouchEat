<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interface Client - Table {{ $tableNumber }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="gradient-bg text-white">
        <div class="container mx-auto px-4 py-6">
            <div class="flex justify-between items-center">
                <div class="text-center flex-1">
                    <h1 class="text-3xl font-bold mb-2">Bienvenue à notre Restaurant</h1>
                    <div class="flex justify-center items-center space-x-4 text-lg">
                        <span>Table N°{{ $tableNumber }}</span>
                        <span class="text-white/70">•</span>
                        <span class="text-white/80">Commandez directement depuis votre table</span>
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST">
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
    <div class="container mx-auto px-4 py-8" x-data="clientInterface()">
        <!-- Navigation Cards (visible seulement sur la page d'accueil) -->
        <template x-if="currentView === 'home'">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-4xl mx-auto mb-12">
                <!-- Menu Card -->
                <button @click="currentView = 'menu'" 
                        class="bg-white rounded-2xl shadow-lg p-8 text-center border border-gray-100 hover:shadow-xl transition-shadow">
                    <div class="text-6xl mb-4">📋</div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-2">Menu</h3>
                    <p class="text-gray-600">Découvrez notre carte</p>
                </button>

                <!-- Panier Card -->
                <button @click="currentView = 'cart'" 
                        class="bg-white rounded-2xl shadow-lg p-8 text-center border border-gray-100 hover:shadow-xl transition-shadow relative">
                    <div class="text-6xl mb-4">🛒</div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-2">Panier</h3>
                    <p class="text-gray-600">Vos articles sélectionnés</p>
                    <template x-if="cartCount > 0">
                        <span class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold shadow-lg" 
                              x-text="cartCount"></span>
                    </template>
                </button>

                <!-- Historique Card -->
                <button @click="currentView = 'history'" 
                        class="bg-white rounded-2xl shadow-lg p-8 text-center border border-gray-100 hover:shadow-xl transition-shadow">
                    <div class="text-6xl mb-4">📜</div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-2">Historique</h3>
                    <p class="text-gray-600">Vos commandes passées</p>
                </button>
            </div>
        </template>

        <!-- Vue Menu -->
        <template x-if="currentView === 'menu'">
            <div class="max-w-7xl mx-auto">
                <!-- Header Menu avec bouton retour et panier -->
                <div class="flex items-center justify-between mb-8">
                    <button 
                        @click="currentView = 'home'" 
                        class="flex items-center space-x-2 text-gray-700 hover:text-blue-600 transition-all duration-300 font-semibold group">
                        <span class="text-2xl group-hover:-translate-x-1 transition-transform duration-300">←</span>
                        <span class="group-hover:text-blue-600">Retour</span>
                    </button>

                    <h1 class="text-4xl font-bold text-gray-800 text-center flex-1">
                        Notre Menu
                    </h1>

                    <!-- Bouton Panier avec badge -->
                    <button 
                        @click="currentView = 'cart'" 
                        class="flex items-center space-x-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 shadow-md hover:shadow-lg hover:scale-105 relative group">
                        <span class="text-xl">🛒</span>
                        <span>Panier</span>
                        <template x-if="cartCount > 0">
                            <span class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold shadow-lg" 
                                  x-text="cartCount"></span>
                        </template>
                        <div class="absolute inset-0 bg-white opacity-0 group-hover:opacity-10 rounded-xl transition-opacity duration-300"></div>
                    </button>
                </div>

                <!-- Category Tabs Simples -->
                <div class="flex justify-center mb-12">
                    <div class="bg-white rounded-lg p-1 shadow-sm border border-gray-200">
                        <button @click="activeCategory = 'repas'" 
                                :class="activeCategory === 'repas' ? 'bg-blue-600 text-white shadow' : 'text-gray-600 hover:text-gray-800 bg-white'"
                                class="px-8 py-3 rounded-md font-semibold transition-all duration-200">
                            🍝Repas
                        </button>
                        <button @click="activeCategory = 'boisson'" 
                                :class="activeCategory === 'boisson' ? 'bg-blue-600 text-white shadow' : 'text-gray-600 hover:text-gray-800 bg-white'"
                                class="px-8 py-3 rounded-md font-semibold transition-all duration-200">
                            🥤Boissons
                        </button>
                    </div>
                </div>

                <!-- Menu Items -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 px-4">
                    <template x-for="item in filteredMenu" :key="item.id">
                        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 transform transition-all duration-300 hover:shadow-xl hover:-translate-y-1 relative overflow-hidden group"
                             :class="{'opacity-60': !item.available}">
                            
                            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-purple-50 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>

                            <div class="relative z-10 flex justify-between items-start mb-4">
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold text-gray-800 mb-2 group-hover:text-blue-700 transition-colors duration-300" x-text="item.name"></h3>
                                    <p class="text-gray-600 text-sm leading-relaxed" x-text="item.description"></p>
                                </div>
                                <template x-if="item.promotion_discount">
                                    <span class="bg-gradient-to-r from-red-500 to-pink-500 text-white px-3 py-1 rounded-full text-xs font-bold ml-4 shadow-md">
                                        PROMO
                                    </span>
                                </template>
                            </div>
                            
                            <div class="relative z-10 flex justify-between items-center mt-6">
                                <div class="flex items-center space-x-3">
                                    <template x-if="item.promotion_discount">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-xl font-bold text-blue-700" x-text="formatPrice(item.price)"></span>
                                            <span class="text-sm text-gray-400 line-through" x-text="formatPrice(item.original_price)"></span>
                                        </div>
                                    </template>
                                    <template x-if="!item.promotion_discount">
                                        <span class="text-xl font-bold text-gray-800" x-text="formatPrice(item.price)"></span>
                                    </template>
                                </div>
                                
                                <div class="flex items-center space-x-3">
                                    <template x-if="!item.available">
                                        <span class="text-sm text-gray-500 font-medium">
                                            Indisponible
                                        </span>
                                    </template>
                                    <template x-if="item.available">
                                        <button 
                                            @click="addToCart(item)" 
                                            class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-6 py-3 rounded-lg font-semibold transition-all shadow-md hover:shadow-lg">
                                            Ajouter
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Empty State -->
                <template x-if="filteredMenu.length === 0">
                    <div class="text-center py-16 bg-white rounded-2xl shadow-sm border border-gray-200">
                        <div class="text-8xl mb-6">🤔</div>
                        <h3 class="text-3xl font-bold text-gray-600 mb-4">
                            Aucun <span x-text="activeCategory === 'repas' ? 'repas' : 'boisson'"></span> disponible
                        </h3>
                        <p class="text-xl text-gray-500 mb-8">Revenez plus tard</p>
                    </div>
                </template>
            </div>
        </template>

        <!-- Vue Panier -->
        <template x-if="currentView === 'cart'">
            <div class="max-w-4xl mx-auto">
                <div class="flex items-center justify-between mb-8">
                    <button 
                        @click="currentView = 'home'" 
                        class="flex items-center space-x-2 text-gray-700 hover:text-blue-600 transition-all duration-300 font-semibold group">
                        <span class="text-2xl group-hover:-translate-x-1 transition-transform duration-300">←</span>
                        <span class="group-hover:text-blue-600">Retour</span>
                    </button>
                    <h2 class="text-3xl font-bold text-gray-800 text-center">
                        Panier <span x-text="cartCount > 0 ? `(${cartCount} article${cartCount > 1 ? 's' : ''})` : ''"></span>
                    </h2>
                    <div class="w-20"></div>
                </div>

                <!-- Empty Cart -->
                <template x-if="cartItems.length === 0">
                    <div class="text-center py-16 bg-white rounded-2xl shadow-sm border border-gray-200">
                        <div class="text-8xl mb-6">🛒</div>
                        <h3 class="text-3xl font-bold text-gray-600 mb-4">Votre panier est vide</h3>
                        <p class="text-xl text-gray-500 mb-8">Ajoutez des articles depuis le menu</p>
                        <button @click="currentView = 'menu'" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-4 rounded-lg text-lg font-semibold transition-all duration-300 shadow-md hover:shadow-lg">
                            Voir le Menu
                        </button>
                    </div>
                </template>

                <!-- Cart with Items -->
                <template x-if="cartItems.length > 0">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Cart Items -->
                        <div class="lg:col-span-2 space-y-4">
                            <template x-for="item in cartItems" :key="item.id">
                                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                    <div class="flex justify-between items-start mb-4">
                                        <div class="flex-1">
                                            <h3 class="text-xl font-semibold text-gray-800" x-text="item.name"></h3>
                                            <p class="text-gray-600 text-sm mt-1" x-text="item.description"></p>
                                            <div class="flex items-center mt-3">
                                                <span class="text-lg font-bold text-gray-800" 
                                                      x-text="formatPrice(item.price)"></span>
                                                <template x-if="item.promotion_discount">
                                                    <span class="bg-red-500 text-white px-2 py-1 rounded-full text-sm ml-2">
                                                        PROMO
                                                    </span>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <button @click="updateQuantity(item.id, item.quantity - 1)" 
                                                    class="bg-gray-100 text-gray-700 w-10 h-10 rounded-lg hover:bg-gray-200 transition-colors font-bold">
                                                -
                                            </button>
                                            <span class="text-xl font-semibold w-8 text-center text-gray-800" 
                                                  x-text="item.quantity"></span>
                                            <button @click="updateQuantity(item.id, item.quantity + 1)" 
                                                    class="bg-gray-100 text-gray-700 w-10 h-10 rounded-lg hover:bg-gray-200 transition-colors font-bold">
                                                +
                                            </button>
                                        </div>
                                        
                                        <div class="text-right">
                                            <div class="text-xl font-bold text-gray-800" 
                                                 x-text="formatPrice(item.price * item.quantity)"></div>
                                            <button @click="updateQuantity(item.id, 0)" 
                                                    class="text-red-500 hover:text-red-700 text-sm font-semibold mt-1 transition-colors duration-300">
                                                Supprimer
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Order Summary -->
                        <div class="lg:col-span-1">
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-6">
                                <h3 class="text-2xl font-bold text-gray-800 mb-4">Récapitulatif</h3>
                                
                                <div class="space-y-3 mb-4">
                                    <template x-for="item in cartItems" :key="item.id">
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600" x-text="`${item.name} x${item.quantity}`"></span>
                                            <span class="font-semibold" x-text="formatPrice(item.price * item.quantity)"></span>
                                        </div>
                                    </template>
                                </div>
                                
                                <div class="border-t pt-4 mb-6">
                                    <div class="flex justify-between items-center text-xl font-bold text-gray-800">
                                        <span>Total</span>
                                        <span x-text="formatPrice(cartTotal)"></span>
                                    </div>
                                </div>
                                
                                <div class="space-y-3">
                                    <button @click="showPaymentModal = true" 
                                            class="w-full bg-blue-600 hover:bg-blue-700 text-white py-4 rounded-lg font-semibold transition-all duration-300 shadow-md hover:shadow-lg">
                                        🍽️ Commander sur place
                                    </button>
                                    
                                    <button @click="showDeliveryModal = true" 
                                            class="w-full bg-green-600 hover:bg-green-700 text-white py-4 rounded-lg font-semibold transition-all duration-300 shadow-md hover:shadow-lg">
                                        🚗 Livraison
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        <!-- Vue Historique -->
        <template x-if="currentView === 'history'">
            <div class="max-w-6xl mx-auto">
                <div class="flex items-center justify-between mb-8">
                    <button 
                        @click="currentView = 'home'" 
                        class="flex items-center space-x-2 text-gray-700 hover:text-blue-600 transition-all duration-300 font-semibold group">
                        <span class="text-2xl group-hover:-translate-x-1 transition-transform duration-300">←</span>
                        <span class="group-hover:text-blue-600">Retour à l'accueil</span>
                    </button>
                    
                    <h2 class="text-3xl font-bold text-gray-800 text-center">
                        Historique des Commandes
                    </h2>
                    
                    <a href="{{ route('client.order.history') }}" 
                       class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition-all duration-300 shadow-md hover:shadow-lg">
                        Voir détail complet
                    </a>
                </div>

                <!-- Résumé des dernières commandes -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Dernières commandes</h3>
                        <p class="text-gray-600 text-sm mb-4">Vos 3 commandes les plus récentes</p>
                        <a href="{{ route('client.order.history') }}" 
                           class="text-blue-600 hover:text-blue-700 font-semibold text-sm">
                            Voir tout l'historique →
                        </a>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Commandes en cours</h3>
                        <p class="text-gray-600 text-sm">Suivez l'état de vos commandes actuelles</p>
                    </div>
                </div>

                <!-- Message d'information -->
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 text-center">
                    <div class="text-4xl mb-4">📋</div>
                    <h3 class="text-xl font-semibold text-blue-800 mb-2">Historique complet disponible</h3>
                    <p class="text-blue-700 mb-4">
                        Consultez toutes vos commandes passées avec les détails complets sur la page dédiée
                    </p>
                    <a href="{{ route('client.order.history') }}" 
                       class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg font-semibold transition-all duration-300 shadow-md hover:shadow-lg">
                        Accéder à l'historique complet
                    </a>
                </div>
            </div>
        </template>

        <!-- Modal de paiement -->
        <template x-if="showPaymentModal">
            <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-2xl p-6 max-w-md w-full shadow-xl">
                    <h3 class="text-2xl font-bold text-gray-800 mb-2">Paiement Mobile Money</h3>
                    <p class="text-gray-600 mb-6">Entrez votre numéro de téléphone pour recevoir la demande de paiement</p>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Numéro de téléphone</label>
                            <input type="tel" x-model="phoneNumber" 
                                   placeholder="ex: 77 123 45 67"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <div class="flex justify-between items-center text-lg font-semibold">
                                <span class="text-gray-700">Total à payer</span>
                                <span class="text-gray-800" x-text="formatPrice(cartTotal)"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex gap-3 mt-6">
                        <button @click="showPaymentModal = false" 
                                class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-3 rounded-lg font-semibold transition-all duration-300">
                            Annuler
                        </button>
                        <button @click="placeOrder('sur_place')" 
                                :disabled="!phoneNumber.trim() || isProcessingPayment"
                                :class="!phoneNumber.trim() || isProcessingPayment ? 
                                    'bg-blue-400 cursor-not-allowed' : 
                                    'bg-blue-600 hover:bg-blue-700'"
                                class="flex-1 text-white py-3 rounded-lg font-semibold transition-all duration-300">
                            <template x-if="isProcessingPayment">Traitement...</template>
                            <template x-if="!isProcessingPayment">Payer</template>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <script>
    function clientInterface() {
        return {
            currentView: 'home',
            activeCategory: 'repas',
            menuItems: @json($menuItems),
            cartItems: @json($cartItems),
            cartCount: {{ $cartCount }},
            cartTotal: 0,
            showPaymentModal: false,
            showDeliveryModal: false,
            phoneNumber: '',
            isProcessingPayment: false,
            hasActiveOrder: @json($currentOrder !== null),
            currentOrder: @json($currentOrder),

            init() {
                this.calculateCartTotal();
            },

            get filteredMenu() {
                return this.menuItems.filter(item => item.category === this.activeCategory);
            },

            formatPrice(price) {
                return new Intl.NumberFormat('fr-FR', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                }).format(price) + ' FCFA';
            },

            calculateCartTotal() {
                this.cartTotal = this.cartItems.reduce((total, item) => total + (item.price * item.quantity), 0);
            },

            async addToCart(item) {
                try {
                    const response = await axios.post('{{ route("client.cart.add") }}', {
                        menu_item_id: item.id,
                        quantity: 1
                    });

                    if (response.data.success) {
                        this.cartCount = response.data.cart_count;
                        this.cartItems = response.data.cart_items || [];
                        this.calculateCartTotal();
                        
                        this.showToast('Article ajouté au panier!');
                    }
                } catch (error) {
                    console.error('Erreur lors de l\'ajout au panier:', error);
                    this.showToast('Erreur lors de l\'ajout au panier', 'error');
                }
            },

            async updateQuantity(itemId, newQuantity) {
                try {
                    const response = await axios.post('{{ route("client.cart.update") }}', {
                        menu_item_id: itemId,
                        quantity: newQuantity
                    });

                    if (response.data.success) {
                        this.cartCount = response.data.cart_count;
                        this.cartItems = response.data.cart_items || [];
                        this.cartTotal = response.data.cart_total || 0;
                        
                        if (newQuantity === 0) {
                            this.showToast('Article supprimé du panier');
                        }
                    }
                } catch (error) {
                    console.error('Erreur lors de la mise à jour du panier:', error);
                    this.showToast('Erreur lors de la mise à jour', 'error');
                }
            },

            async placeOrder(orderType) {
                if (!this.phoneNumber.trim()) {
                    this.showToast('Veuillez entrer votre numéro de téléphone', 'error');
                    return;
                }

                this.isProcessingPayment = true;

                try {
                    const response = await axios.post('{{ route("client.order.place") }}', {
                        order_type: orderType,
                        phone_number: this.phoneNumber
                    });

                    if (response.data.success) {
                        this.showPaymentModal = false;
                        this.hasActiveOrder = true;
                        this.cartItems = [];
                        this.cartCount = 0;
                        this.cartTotal = 0;
                        this.phoneNumber = '';
                        
                        this.showToast('Commande passée avec succès!');
                        
                        if (response.data.redirect_url) {
                            setTimeout(() => {
                                window.location.href = response.data.redirect_url;
                            }, 1500);
                        }
                    } else {
                        this.showToast(response.data.message || 'Erreur lors de la commande', 'error');
                    }
                } catch (error) {
                    console.error('Erreur lors de la commande:', error);
                    if (error.response && error.response.data.message) {
                        this.showToast(error.response.data.message, 'error');
                    } else {
                        this.showToast('Erreur lors de la commande', 'error');
                    }
                } finally {
                    this.isProcessingPayment = false;
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
            }
        }
    }
    </script>
</body>
</html>
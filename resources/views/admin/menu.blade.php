@extends('layouts.admin')

@section('content')
<div class="space-y-6" x-data="menuComponent()">
    <div class="flex justify-between items-center">
        <h2 class="text-3xl font-bold">Gestion du Menu</h2>
        <button @click="showAddModal = true" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
            ➕ Ajouter un Article
        </button>
    </div>

    <!-- Onglets du menu -->
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
            <button @click="loadMenuWithCategory('repas')" 
                   :class="activeCategory === 'repas' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                   class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                🍽️ Repas ({{ $categories['repas'] }})
            </button>
            <button @click="loadMenuWithCategory('boisson')" 
                   :class="activeCategory === 'boisson' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                   class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                🥤 Boissons ({{ $categories['boisson'] }})
            </button>
        </nav>
    </div>

    <!-- Contenu des onglets -->
    <div x-show="activeCategory === 'repas'">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($menuItems->where('category', 'repas') as $item)
            <x-ui.menu-item-card :item="$item" />
            @endforeach
        </div>
        
        @if($menuItems->where('category', 'repas')->isEmpty())
        <div class="bg-white rounded-lg shadow text-center py-12">
            <div class="text-6xl mb-4">🍽️</div>
            <h3 class="text-xl font-semibold text-gray-600 mb-2">
                Aucun repas dans le menu
            </h3>
            <button @click="showAddModal = true" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 mt-4">
                Ajouter le premier repas
            </button>
        </div>
        @endif
    </div>

    <div x-show="activeCategory === 'boisson'">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($menuItems->where('category', 'boisson') as $item)
            <x-ui.menu-item-card :item="$item" />
            @endforeach
        </div>
        
        @if($menuItems->where('category', 'boisson')->isEmpty())
        <div class="bg-white rounded-lg shadow text-center py-12">
            <div class="text-6xl mb-4">🥤</div>
            <h3 class="text-xl font-semibold text-gray-600 mb-2">
                Aucune boisson dans le menu
            </h3>
            <button @click="showAddModal = true" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 mt-4">
                Ajouter la première boisson
            </button>
        </div>
        @endif
    </div>

    <!-- Modal Ajouter/Modifier Article -->
    <div x-show="showAddModal || showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4" x-text="editingItem ? 'Modifier l\'article' : 'Ajouter un nouvel article'"></h3>
                
                <form x-bind:action="editingItem ? '/admin/menu/' + editingItem.id : '/admin/menu'" 
                      method="POST" 
                      x-on:submit="handleSaveItem">
                    @csrf
                    <div x-show="editingItem">
                        @method('PUT')
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nom de l'article</label>
                            <input type="text" name="name" x-model="formData.name" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="ex: Burger Classique">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea name="description" x-model="formData.description" required
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                      placeholder="Décrivez l'article..."></textarea>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Prix (FCFA)</label>
                                <input type="number" name="price" x-model="formData.price" required min="0"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       placeholder="0">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Catégorie</label>
                                <select name="category" x-model="formData.category" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="repas">🍽️ Repas</option>
                                    <option value="boisson">🥤 Boisson</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            <input type="checkbox" name="available" x-model="formData.available" 
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label class="text-sm font-medium text-gray-700">Article disponible</label>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" @click="closeModal" 
                                class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Annuler
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            <span x-text="editingItem ? 'Sauvegarder' : 'Ajouter l\'article'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Promotion (CORRIGÉ) -->
    <div x-show="showPromotionModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Ajouter une Promotion</h3>
                
                <form x-bind:action="'/admin/menu/' + editingItem.id + '/promotion'" 
                      method="POST" 
                      x-on:submit="handleSavePromotion">
                    @csrf
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Pourcentage de réduction (%)</label>
                            <input type="number" name="discount" x-model="promotionData.discount" required min="1" max="99"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="ex: 15">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Prix original (FCFA)</label>
                            <input type="number" name="original_price" x-model="promotionData.originalPrice" required min="0"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Prix avant réduction">
                        </div>
                        
                        <div x-show="promotionData.discount && promotionData.originalPrice" 
                             class="bg-gray-100 p-3 rounded">
                            <p class="text-sm font-medium">Aperçu:</p>
                            <div class="flex items-center space-x-2 mt-1">
                                <span class="text-lg font-bold text-green-600" 
                                      x-text="formatPrice(promotionData.originalPrice * (1 - (promotionData.discount / 100)))">
                                </span>
                                <span class="text-sm text-gray-500 line-through" 
                                      x-text="formatPrice(promotionData.originalPrice)">
                                </span>
                                <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs font-semibold"
                                      x-text="'-' + promotionData.discount + '%'">
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" @click="showPromotionModal = false" 
                                class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Annuler
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                            Appliquer la Promotion
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function menuComponent() {
    return {
        activeCategory: 'repas',
        showAddModal: false,
        showEditModal: false,
        showPromotionModal: false,
        editingItem: null,
        formData: {
            name: '',
            description: '',
            price: 0,
            category: 'repas',
            available: true
        },
        promotionData: {
            discount: 0,
            originalPrice: 0
        },

        // Fonction pour formater les prix
        formatPrice(price) {
            return new Intl.NumberFormat('fr-FR', {
                maximumFractionDigits: 0
            }).format(price) + ' FCFA';
        },

        loadMenuWithCategory(category) {
            this.activeCategory = category;
        },

        editItem(item) {
            this.editingItem = item;
            this.formData = { ...item };
            this.showEditModal = true;
        },

        addPromotion(item) {
            this.editingItem = item;
            this.promotionData.originalPrice = item.price;
            this.showPromotionModal = true;
        },

        closeModal() {
            this.showAddModal = false;
            this.showEditModal = false;
            this.showPromotionModal = false;
            this.editingItem = null;
            this.formData = {
                name: '',
                description: '',
                price: 0,
                category: 'repas',
                available: true
            };
            this.promotionData = {
                discount: 0,
                originalPrice: 0
            };
        },

        handleSaveItem(e) {
            return true;
        },

        handleSavePromotion(e) {
            return true;
        }
    }
}
</script>
@endsection
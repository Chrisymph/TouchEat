<!-- Dans resources/views/admin/menu-content.blade.php -->
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-3xl font-bold">Gestion du Menu</h2>
        <button data-add-item class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
            ➕ Ajouter un Article
        </button>
    </div>

    <!-- Onglets du menu -->
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
            <button data-category="repas" 
                   class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200 
                          {{ $category === 'repas' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                🍽️ Repas ({{ $categories['repas'] }})
            </button>
            <button data-category="boisson" 
                   class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200 
                          {{ $category === 'boisson' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                🥤 Boissons ({{ $categories['boisson'] }})
            </button>
        </nav>
    </div>

    <!-- Contenu des onglets -->
    <div>
        @if($category === 'repas')
            @if($menuItems->where('category', 'repas')->isEmpty())
            <div class="bg-white rounded-lg shadow text-center py-12">
                <div class="text-6xl mb-4">🍽️</div>
                <h3 class="text-xl font-semibold text-gray-600 mb-2">
                    Aucun repas dans le menu
                </h3>
                <button data-add-item class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 mt-4">
                    Ajouter le premier repas
                </button>
            </div>
            @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($menuItems->where('category', 'repas') as $item)
                <x-ui.menu-item-card :item="$item" />
                @endforeach
            </div>
            @endif
        @else
            @if($menuItems->where('category', 'boisson')->isEmpty())
            <div class="bg-white rounded-lg shadow text-center py-12">
                <div class="text-6xl mb-4">🥤</div>
                <h3 class="text-xl font-semibold text-gray-600 mb-2">
                    Aucune boisson dans le menu
                </h3>
                <button data-add-item class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 mt-4">
                    Ajouter la première boisson
                </button>
            </div>
            @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($menuItems->where('category', 'boisson') as $item)
                <x-ui.menu-item-card :item="$item" />
                @endforeach
            </div>
            @endif
        @endif
    </div>
</div>
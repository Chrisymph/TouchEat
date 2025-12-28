<!-- Dans resources/views/admin/menu-content.blade.php -->
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-3xl font-bold">Gestion du Menu</h2>
        <button data-add-item class="bg-orange-500 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
            ➕ Ajouter un Article
        </button>
    </div>

    <!-- Onglets du menu -->
<div class="mb-6 flex justify-center">
<div class="bg-white shadow rounded-lg overflow-hidden w-full max-w-7xl"> 
    <div class="flex divide-x divide-gray-200">
      
      <button data-category="repas" 
              class="w-1/2 py-3 font-semibold text-sm transition-all duration-200
              {{ $category === 'repas' 
                  ? 'bg-orange-100 text-orange-600' 
                  : 'bg-gray-50 text-gray-500 hover:bg-gray-100' }}">
        🍽️ Repas ({{ $categories['repas'] }})
      </button>

      <button data-category="boisson" 
              class="w-1/2 py-3 font-semibold text-sm transition-all duration-200
              {{ $category === 'boisson' 
                  ? 'bg-orange-100 text-orange-600' 
                  : 'bg-gray-50 text-gray-500 hover:bg-gray-100' }}">
        🥤 Boissons ({{ $categories['boisson'] }})
      </button>

    </div>
  </div> 
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
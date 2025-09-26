@props(['item'])

<div class="bg-white rounded-lg shadow transition-all duration-200 hover:shadow-lg">
    <div class="p-6">
        <div class="flex justify-between items-start mb-4">
            <div class="flex-1">
                <h3 class="text-lg font-semibold">{{ $item->name }}</h3>
                <p class="text-gray-600 text-sm mt-1">{{ $item->description }}</p>
            </div>
            <div class="flex flex-col items-end space-y-2">
                @if($item->promotion_discount)
                    <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs font-semibold mb-1">
                        -{{ $item->promotion_discount }}%
                    </span>
                    <div class="text-lg font-bold text-green-600">
                        {{ number_format($item->price, 0, ',', ' ') }} FCFA
                    </div>
                    <div class="text-sm text-gray-500 line-through">
                        {{ number_format($item->original_price, 0, ',', ' ') }} FCFA
                    </div>
                @else
                    <div class="text-lg font-bold text-blue-600">
                        {{ number_format($item->price, 0, ',', ' ') }} FCFA
                    </div>
                @endif
                @if(!$item->available)
                    <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs font-semibold">
                        Indisponible
                    </span>
                @endif
            </div>
        </div>
        
        <div class="flex justify-between items-center space-x-2">
            <div class="flex items-center space-x-2">
                <input type="checkbox" 
                       onchange="handleToggleAvailability({{ $item->id }})"
                       {{ $item->available ? 'checked' : '' }}
                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="text-sm text-gray-700">Disponible</span>
            </div>
            
            <div class="flex space-x-2">
                @if($item->promotion_discount)
                    <button onclick="handleRemovePromotion({{ $item->id }})" 
                            class="bg-gray-100 text-gray-700 px-3 py-1 rounded text-sm hover:bg-gray-200">
                        🏷️ Retirer Promo
                    </button>
                @else
                    <button onclick="handleAddPromotion({{ $item->id }})" 
                            class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded text-sm hover:bg-yellow-200">
                        🏷️ Promotion
                    </button>
                @endif
                
                <button onclick="handleEditItem({{ json_encode($item) }})" 
                        class="bg-blue-100 text-blue-700 px-3 py-1 rounded text-sm hover:bg-blue-200">
                    ✏️ Modifier
                </button>
                
                <form action="/admin/menu/{{ $item->id }}" method="POST" class="inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet article ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            class="bg-red-100 text-red-700 px-3 py-1 rounded text-sm hover:bg-red-200">
                        🗑️
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
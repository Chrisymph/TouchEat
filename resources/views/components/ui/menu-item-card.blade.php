@props(['item'])

<div class="bg-white rounded-lg shadow transition-all duration-200 hover:shadow-lg w-full">
    <div class="p-6">
        <div class="flex justify-between items-start mb-4">
            <div class="flex-1">
                <h3 class="text-lg font-semibold">{{ $item->name }}</h3>
                <p class="text-gray-600 text-sm mt-1">{{ $item->description }}</p>
            </div>
            <div class="flex flex-col items-end space-y-2">
                @if($item->promotion_discount)
                    <span class="bg-yellow-400 text-black-800 px-2 py-1 rounded-full text-xs font-semibold mb-1">
                        -{{ $item->promotion_discount }}%
                    </span>
                    <div class="text-lg font-bold text-yellow-500">
                        {{ number_format($item->price, 0, ',', ' ') }} FCFA
                    </div>
                    <div class="text-sm text-gray-500 line-through">
                        {{ number_format($item->original_price, 0, ',', ' ') }} FCFA
                    </div>
                @else
                    <div class="text-lg font-bold text-orange-600">
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
                <label class="flex items-center cursor-pointer">
                    <input 
                        type="checkbox" 
                        id="switch-{{ $item->id }}"
                        onchange="handleToggleAvailability({{ $item->id }})"
                        {{ $item->available ? 'checked' : '' }}
                        class="hidden">

                    <div class="relative w-10 h-5 rounded-full transition-colors duration-300"
                        onclick="toggleSwitch('{{ $item->id }}')"
                        style="background-color: {{ $item->available ? '#f97316' : '#ef4444' }};">
                        <span id="knob-{{ $item->id }}" 
                            class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full transition-transform duration-300"
                            style="transform: {{ $item->available ? 'translateX(20px)' : 'translateX(0)' }};">
                        </span>
                    </div>
                    <span class="ml-2 text-sm text-gray-700">Disponible</span>
                </label>
            </div>
            
            <div class="flex space-x-2">
                @if($item->promotion_discount)
                    <button onclick="handleRemovePromotion({{ $item->id }})" 
                            class="bg-gray-100 text-gray-700 px-2 py-2 rounded text-sm hover:bg-gray-200 flex items-center gap-1">
                            <span>🏷️</span>
                            <span>Retirer Promo</span>        
                    </button>
                @else
                    <button onclick="handleAddPromotion({{ $item->id }})" 
                            class="bg-ray-100 text-yellow-700 px-2 py-2 rounded text-sm hover:bg-yellow-200 flex items-center gap-1">
                            <span>🏷️</span>
                            <span>Promotion</span>
                    </button>
                @endif
                
                <button onclick="handleEditItem({{ json_encode($item) }})" 
                        class="bg-gray-100 text-black-800 px-2 py-2 rounded text-sm hover:bg-blue-200 flex items-center gap-1">
                        <span>✏️</span>
                        <span>Modifier</span>
                     
                </button>
                
                <!-- REMPLACER le formulaire par un bouton avec fonction JavaScript -->
                <button onclick="handleDeleteItem({{ $item->id }})" 
                        class="bg-red-500 text-red-700 px-2 py-2 rounded text-sm hover:bg-red-700">
                    🗑️
                </button>
            </div>
        </div>
    </div>
</div>
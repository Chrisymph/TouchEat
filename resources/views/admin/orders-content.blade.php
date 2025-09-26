<div class="space-y-6">
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

    <!-- Onglets des commandes - CORRIGÉ avec data-status -->
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

                    <!-- Actions -->
                    <div class="flex space-x-2">
                        <a href="{{ route('admin.orders.show', $order->id) }}" 
                           class="flex-1 bg-gray-100 text-gray-700 px-3 py-2 rounded text-sm text-center hover:bg-gray-200">
                            Voir Détails
                        </a>
                        
                        @if(in_array($order->status, ['commandé', 'en_cours', 'prêt']))
                        <form action="{{ route('admin.orders.status', $order->id) }}" method="POST" class="flex-1">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="{{ 
                                $order->status === 'commandé' ? 'en_cours' :
                                ($order->status === 'en_cours' ? 'prêt' : 'terminé')
                            }}">
                            <button type="submit" class="w-full bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700">
                                {{ $order->status === 'commandé' ? 'Accepter' :
                                   ($order->status === 'en_cours' ? 'Marquer Prêt' : 'Terminer') }}
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>
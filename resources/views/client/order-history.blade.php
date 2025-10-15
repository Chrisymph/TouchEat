<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique des Commandes - Table {{ $tableNumber }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- En-tête -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Historique des Commandes</h1>
                    <p class="text-gray-600">Table {{ $tableNumber }}</p>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="{{ route('client.dashboard') }}" 
                       class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                        ← Retour
                    </a>
                </div>
            </div>
        </div>

        <!-- Liste des commandes -->
        <div class="space-y-6">
            @forelse($orders as $order)
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-800">Commande #{{ $order->id }}</h3>
                        <p class="text-gray-600">
                            {{ $order->created_at->format('d/m/Y H:i') }} - 
                            {{ ucfirst($order->order_type) }}
                        </p>
                    </div>
                    <div class="text-right">
                        <span class="px-3 py-1 rounded-full text-sm font-semibold 
                            @if($order->status === 'terminé') bg-green-100 text-green-800
                            @elseif($order->status === 'prêt') bg-blue-100 text-blue-800
                            @elseif($order->status === 'en_cours') bg-yellow-100 text-yellow-800
                            @else bg-gray-100 text-gray-800 @endif">
                            {{ ucfirst($order->status) }}
                        </span>
                        <p class="text-lg font-bold text-gray-800 mt-2">
                            {{ number_format($order->total, 2, ',', ' ') }} FCFA
                        </p>
                    </div>
                </div>

                <!-- Articles de la commande -->
                <div class="border-t pt-4">
                    @foreach($order->items as $item)
                    <div class="flex justify-between items-center py-2">
                        <div>
                            <span class="font-medium">{{ $item->menuItem->name }}</span>
                            <span class="text-gray-600 text-sm ml-2">x{{ $item->quantity }}</span>
                        </div>
                        <span class="font-semibold">
                            {{ number_format($item->unit_price * $item->quantity, 2, ',', ' ') }} FCFA
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @empty
            <div class="text-center py-16 bg-white rounded-lg shadow-md">
                <div class="text-8xl mb-6">📜</div>
                <h3 class="text-3xl font-bold text-gray-600 mb-4">Aucune commande</h3>
                <p class="text-xl text-gray-500 mb-8">Vos commandes apparaîtront ici</p>
                <a href="{{ route('client.dashboard') }}" 
                   class="bg-blue-600 text-white px-8 py-4 rounded-md text-lg font-semibold hover:bg-blue-700">
                    Passer une commande
                </a>
            </div>
            @endforelse
        </div>
    </div>
</body>
</html>
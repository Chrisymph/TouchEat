<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique des Commandes - Table {{ $tableNumber }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
        background: linear-gradient(180deg, #fbefe9 0%, #f9eae4 100%);
        font-family: 'Poppins', sans-serif;
        color: #2b2b2b;
    }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- En-tête -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Historique des Commandes</h1>
                    <p class="text-gray-600">Table {{ $tableNumber }}</p>
                    <p class="text-sm text-gray-500 mt-1">
                        Toutes vos commandes, payées et en attente de paiement
                    </p>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="{{ route('client.dashboard') }}" 
                        {{-- class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 transition-colors"> --}}
                        class="flex items-center space-x-2 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 shadow-md hover:shadow-lg hover:scale-105 bg-gradient-to-r from-orange-600 to-orange-400 hover:from-orange-500 hover:to-orange-700 relative group">
                        ← Retour au Dashboard
                    </a>
                </div>
            </div>
        </div>



        <!-- Filtres -->
        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
            <div class="flex flex-wrap gap-4 items-center">
                <span class="text-sm font-bold text-gray-700">Légende :</span>
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                    <span class="text-sm">Payée</span>
                </div>
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-yellow-500 rounded-full mr-2"></div>
                    <span class="text-sm">En attente de paiement</span>
                </div>
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-blue-500 rounded-full mr-2"></div>
                    <span class="text-sm">Terminée</span>
                </div>
            </div>
        </div>

        <!-- Liste des commandes -->
        <div class="space-y-6">
            @forelse($orders as $order)
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 
                @if($order->payment_status === 'payé') border-green-500
                @elseif($order->payment_status === 'en_attente') border-yellow-500
                @else border-gray-500 @endif
                transform transition-all duration-300 hover:shadow-lg">
                
                <div class="flex justify-between items-start mb-4">
                    <div class="flex-1">
                        <h3 class="text-xl font-semibold text-gray-800 mb-1">Commande #{{ $order->id }}</h3>
                        <div class="flex flex-wrap gap-4 text-sm text-gray-600">
                            <span>📅 {{ $order->created_at->format('d/m/Y H:i') }}</span>
                            <span>•</span>
                            <span>📍 {{ ucfirst($order->order_type) }}</span>
                            <span>•</span>
                            <span>📞 {{ $order->customer_phone }}</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <!-- Statut de paiement -->
                        <div class="mb-2">
                            <span class="px-3 py-1 rounded-full text-sm font-semibold 
                                @if($order->payment_status === 'payé') bg-green-100 text-green-800 border border-green-200
                                @elseif($order->payment_status === 'en_attente') bg-yellow-100 text-yellow-800 border border-yellow-200
                                @else bg-gray-100 text-gray-800 border border-gray-200 @endif">
                                💰 {{ ucfirst($order->payment_status) }}
                            </span>
                        </div>
                        
                        <!-- Statut de la commande -->
                        <span class="px-3 py-1 rounded-full text-sm font-semibold 
                            @if($order->status === 'terminé') bg-green-100 text-green-800 border border-green-200
                            @elseif($order->status === 'prêt') bg-blue-100 text-blue-800 border border-blue-200
                            @elseif($order->status === 'en_cours') bg-yellow-100 text-yellow-800 border border-yellow-200
                            @elseif($order->status === 'commandé') bg-purple-100 text-purple-800 border border-purple-200
                            @else bg-gray-100 text-gray-800 border border-gray-200 @endif">
                            @if($order->status === 'terminé') ✅
                            @elseif($order->status === 'prêt') 🎯
                            @elseif($order->status === 'en_cours') 👨‍🍳
                            @elseif($order->status === 'commandé') 📥
                            @endif
                            {{ ucfirst($order->status) }}
                        </span>
                        
                        <p class="text-lg font-bold text-gray-800 mt-2">
                            {{ number_format($order->total, 2, ',', ' ') }} FCFA
                        </p>
                    </div>
                </div>

                <!-- Articles de la commande -->
                <div class="border-t pt-4">
                    <h4 class="font-semibold text-gray-700 mb-3 flex items-center">
                        <span class="mr-2">📦</span>
                        Articles commandés :
                    </h4>
                    <div class="space-y-2">
                        @foreach($order->items as $item)
                        <div class="flex justify-between items-center py-2 px-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center">
                                <span class="font-medium text-gray-800">{{ $item->menuItem->name }}</span>
                                <span class="text-gray-600 text-sm ml-3 bg-white px-2 py-1 rounded border">
                                    x{{ $item->quantity }}
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="font-semibold text-gray-800">
                                    {{ number_format($item->unit_price * $item->quantity, 2, ',', ' ') }} FCFA
                                </span>
                                <p class="text-sm text-gray-500">
                                    {{ number_format($item->unit_price, 2, ',', ' ') }} FCFA l'unité
                                </p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Message selon le statut de paiement -->
                @if($order->payment_status === 'en_attente')
                <div class="border-t pt-4 mt-4 bg-yellow-50 rounded-lg p-3">
                    <p class="text-yellow-700 text-sm flex items-center">
                        <span class="mr-2">⚠️</span>
                        <strong>En attente de paiement :</strong> Cette commande n'est pas encore réglée.
                    </p>
                </div>
                @elseif($order->payment_status === 'payé')
                <div class="border-t pt-4 mt-4 bg-green-50 rounded-lg p-3">
                    <p class="text-green-700 text-sm flex items-center">
                        <span class="mr-2">✅</span>
                        <strong>Paiement confirmé :</strong> Cette commande a été réglée.
                    </p>
                </div>
                @endif
            </div>
            @empty
            <div class="text-center py-16 bg-white rounded-lg shadow-md">
                <div class="text-8xl mb-6">📜</div>
                <h3 class="text-3xl font-bold text-gray-600 mb-4">Aucune commande trouvée</h3>
                <p class="text-xl text-gray-500 mb-8">
                    Vous n'avez pas encore passé de commande.
                </p>
                <a href="{{ route('client.dashboard') }}" 
                   class="bg-orange-600 text-white px-8 py-4 rounded-md text-lg font-semibold hover:bg-orange-700 transition-colors">
                    Passer votre première commande
                </a>
            </div>
            @endforelse
        </div>

        <!-- Pagination ou information -->
        @if($orders->count() > 0)
        <div class="mt-8 text-center">
            <p class="text-gray-600">
                Affichage de <strong>{{ $orders->count() }}</strong> commande(s) au total
            </p>
        </div>
        @endif
    </div>
</body>
</html>
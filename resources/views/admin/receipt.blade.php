<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu Commande #{{ $order->id }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { margin: 0; padding: 0; }
            .no-print { display: none !important; }
            .receipt-container { box-shadow: none !important; margin: 0 !important; }
            .page-break { page-break-after: always; }
            @page { margin: 0; }
        }
        
        .receipt-container {
            max-width: 80mm;
            margin: 0 auto;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.2;
        }
        
        .receipt-header, .receipt-footer {
            text-align: center;
            margin-bottom: 10px;
        }
        
        .receipt-items {
            margin: 15px 0;
        }
        
        .receipt-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            padding-bottom: 3px;
            border-bottom: 1px dotted #ccc;
        }
        
        .receipt-total {
            border-top: 2px solid #000;
            padding-top: 10px;
            margin-top: 10px;
        }
        
        .barcode {
            text-align: center;
            margin: 10px 0;
            font-family: 'Libre Barcode 39', monospace;
            font-size: 24px;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Barre d'outils pour l'aperçu -->
    <div class="no-print p-4 bg-white shadow-lg rounded-lg max-w-md mx-auto mt-4">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-xl font-bold">Aperçu du Reçu</h1>
            <div class="flex space-x-2">
                <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition-colors">
                    🖨️ Imprimer
                </button>
                <button onclick="window.close()" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 transition-colors">
                    ✕ Fermer
                </button>
            </div>
        </div>
        <p class="text-sm text-gray-600 mb-4">
            Cet aperçu sera optimisé pour l'impression sur ticket de caisse.
        </p>
    </div>

    <!-- Contenu du reçu -->
    <div class="receipt-container bg-white">
        <!-- En-tête du reçu -->
        <div class="receipt-header">
            <h1 class="text-lg font-bold uppercase">{{ $admin->restaurant_name ?? 'RESTAURANT' }}</h1>
            <p class="text-xs">{{ $admin->restaurant_address ?? 'Adresse du restaurant' }}</p>
            <p class="text-xs">Tél: {{ $admin->restaurant_phone ?? '01 58 66 94 96' }}</p>
            <div class="border-t border-b border-black my-2 py-1">
                <p class="font-bold">REÇU DE COMMANDE</p>
            </div>
        </div>

        <!-- Informations de la commande -->
        <div class="mb-3">
            <div class="flex justify-between">
                <span>Commande #:</span>
                <span class="font-bold">{{ $order->id }}</span>
            </div>
            <div class="flex justify-between">
                <span>Date:</span>
                <span>{{ $order->created_at->format('d/m/Y H:i') }}</span>
            </div>
            <div class="flex justify-between">
                <span>Type:</span>
                <span>
                    @if($order->order_type === 'sur_place')
                        Sur place
                    @elseif($order->order_type === 'emporter')
                        À emporter
                    @else
                        Livraison
                    @endif
                </span>
            </div>
            @if($order->table_number)
            <div class="flex justify-between">
                <span>Table:</span>
                <span>{{ $order->table_number }}</span>
            </div>
            @endif
            @if($order->customer_phone)
            <div class="flex justify-between">
                <span>Client:</span>
                <span>{{ $order->customer_phone }}</span>
            </div>
            @endif
        </div>

        <!-- Ligne séparatrice -->
        <div class="border-b border-black my-2"></div>

        <!-- Articles commandés -->
        <div class="receipt-items">
            <div class="font-bold mb-2 text-center">
                ARTICLES COMMANDÉS
            </div>
            
            @foreach($order->items as $item)
            <div class="receipt-item">
                <div class="flex-1">
                    <div class="font-semibold">
                        {{ $item->menuItem->name ?? $item->name }}
                    </div>
                    <div class="text-xs">
                        {{ number_format($item->unit_price, 0, ',', ' ') }} FCFA x {{ $item->quantity }}
                    </div>
                </div>
                <div class="font-semibold text-right">
                    {{ number_format($item->unit_price * $item->quantity, 0, ',', ' ') }} FCFA
                </div>
            </div>
            @endforeach
        </div>

        <!-- Ligne séparatrice -->
        <div class="border-b border-black my-2"></div>

        <!-- Total -->
        <div class="receipt-total">
            <div class="flex justify-between font-bold text-lg">
                <span>TOTAL:</span>
                <span>{{ number_format($order->total, 0, ',', ' ') }} FCFA</span>
            </div>
        </div>

        <!-- Pied de page -->
        <div class="receipt-footer mt-4">
            <p class="text-xs">*** MERCI DE VOTRE VISITE ***</p>
            <p class="text-xs">Nous espérons vous revoir bientôt</p>
        </div>
    </div>

    <script>
        // Impression automatique si demandé
        @if(request()->has('auto_print'))
        window.onload = function() {
            setTimeout(() => {
                window.print();
            }, 500);
        }
        @endif

        // Fermer après impression (optionnel)
        window.onafterprint = function() {
            setTimeout(() => {
                window.close();
            }, 1000);
        };

        // Alternative: fermer avec Échap
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                window.close();
            }
        });
    </script>
</body>
</html>
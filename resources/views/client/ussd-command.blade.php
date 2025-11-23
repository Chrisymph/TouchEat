<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commande USSD - Table {{ $tableNumber }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alternative QR Code Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"></script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .ussd-code {
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="gradient-bg text-white">
        <div class="container mx-auto px-4 py-6">
            <div class="flex justify-between items-center">
                <div class="text-center flex-1">
                    <h1 class="text-3xl font-bold mb-2">Paiement Mobile Money</h1>
                    <div class="flex justify-center items-center space-x-4 text-lg">
                        <span>Table N°{{ $tableNumber }}</span>
                        <span class="text-white/70">•</span>
                        <span class="text-white/80">Commande #{{ $order->id }}</span>
                    </div>
                </div>
                <a href="{{ route('client.dashboard') }}" 
                   class="bg-white/20 hover:bg-white/30 text-white px-6 py-2 rounded-lg font-semibold transition-colors backdrop-blur-sm">
                    Retour
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <!-- Carte de confirmation -->
            <div class="bg-white rounded-2xl shadow-lg p-8 mb-8 border border-green-200">
                <div class="text-center mb-6">
                    <div class="text-6xl mb-4">✅</div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Commande Confirmée!</h2>
                    <p class="text-gray-600">Votre commande #{{ $order->id }} a été enregistrée avec succès</p>
                </div>
                
                <div class="bg-gray-50 rounded-xl p-6 mb-6">
                    <div class="flex justify-between items-center text-lg">
                        <span class="font-semibold text-gray-700">Total à payer:</span>
                        <span class="font-bold text-2xl text-green-600">
                            {{ number_format($order->total, 0, ',', ' ') }} FCFA
                        </span>
                    </div>
                </div>
            </div>

            <!-- Instructions de paiement -->
            <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
                <h3 class="text-2xl font-bold text-gray-800 mb-6 text-center">
                    Instructions de Paiement - 
                    <span class="text-blue-600">
                        @if($selectedNetwork === 'mtn')
                            MTN Money
                        @elseif($selectedNetwork === 'moov')
                            Moov Money
                        @else
                            Orange Money
                        @endif
                    </span>
                </h3>

                <!-- Code USSD -->
                <div class="text-center mb-8">
                    <div class="ussd-code p-6 rounded-lg mb-4 text-2xl font-bold text-gray-800 tracking-wider select-all">
                        {{ $ussdCommand }}
                    </div>
                    <p class="text-gray-600 mb-2">Copiez cette commande USSD manuellement dans votre application téléphone</p>
                </div>

                <!-- QR Code avec alternative -->
                <div class="text-center border-t pt-8">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">
                        📱 Ou scannez le QR Code
                    </h4>
                    <div class="flex justify-center mb-4">
                        <div id="qrcode" class="p-4 bg-white rounded-lg border border-gray-200 inline-block"></div>
                    </div>
                    <p class="text-gray-600 mt-4 text-sm">
                        Scannez ce code avec votre application Mobile Money pour copier automatiquement la commande
                    </p>
                </div>
            </div>

            <!-- Instructions étape par étape -->
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <h4 class="text-xl font-bold text-gray-800 mb-4">Comment procéder :</h4>
                <div class="space-y-4">
                    <div class="flex items-start space-x-3">
                        <span class="bg-blue-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold mt-1 flex-shrink-0">1</span>
                        <p class="text-gray-700">Ouvrez votre application Téléphone sur votre Android</p>
                    </div>
                    <div class="flex items-start space-x-3">
                        <span class="bg-blue-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold mt-1 flex-shrink-0">2</span>
                        <p class="text-gray-700">Copiez manuellement la commande USSD, soit scannez le QR Code puis copiez et coller le texte dans votre application Téléphone </p>
                    </div>
                    <div class="flex items-start space-x-3">
                        <span class="bg-blue-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold mt-1 flex-shrink-0">3</span>
                        <p class="text-gray-700">Confirmez la transaction avec votre code PIN</p>
                    </div>
                    <div class="flex items-start space-x-3">
                        <span class="bg-blue-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold mt-1 flex-shrink-0">4</span>
                        <p class="text-gray-700">Votre paiement sera traité automatiquement</p>
                    </div>
                </div>
            </div>

            <!-- Bouton pour saisir l'ID de transaction -->
            <div class="text-center mt-8 space-y-4">
                <a href="{{ route('client.payment.form', $order->id) }}" 
                   class="inline-block bg-green-600 hover:bg-green-700 text-white px-8 py-4 rounded-lg font-semibold transition-all duration-300 shadow-md hover:shadow-lg">
                    ✅ J'ai payé - Saisir l'ID de Transaction
                </a>
                
                <div>
                    <a href="{{ route('client.dashboard') }}" 
                       class="inline-block bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg font-semibold transition-all duration-300">
                        ← Retour à l'accueil
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Alternative QR Code Generation
        document.addEventListener('DOMContentLoaded', function() {
            const ussdCommand = "{{ $ussdCommand }}";
            const qrcodeElement = document.getElementById('qrcode');
            
            try {
                // Utilisation de la bibliothèque qrcode-generator
                const typeNumber = 4; // Type de QR code
                const errorCorrectionLevel = 'M'; // Niveau de correction d'erreur
                const qr = qrcode(typeNumber, errorCorrectionLevel);
                qr.addData(ussdCommand);
                qr.make();
                
                // Créer un élément canvas pour le QR code
                const canvas = document.createElement('canvas');
                const size = 200;
                const cellSize = size / qr.getModuleCount();
                const context = canvas.getContext('2d');
                
                canvas.width = size;
                canvas.height = size;
                
                // Dessiner le QR code
                for (let row = 0; row < qr.getModuleCount(); row++) {
                    for (let col = 0; col < qr.getModuleCount(); col++) {
                        context.fillStyle = qr.isDark(row, col) ? '#000000' : '#FFFFFF';
                        context.fillRect(
                            col * cellSize,
                            row * cellSize,
                            cellSize,
                            cellSize
                        );
                    }
                }
                
                // Ajouter le canvas au DOM
                qrcodeElement.appendChild(canvas);
                
            } catch (error) {
                console.error('Erreur génération QR code:', error);
                // Fallback simple
                qrcodeElement.innerHTML = `
                    <div class="text-center p-8 border-2 border-dashed border-gray-300 rounded-lg">
                        <div class="text-4xl mb-2">📱</div>
                        <p class="text-gray-600 text-sm">Utilisez le code USSD ci-dessus</p>
                        <p class="text-gray-500 text-xs mt-2">(QR code non disponible)</p>
                    </div>
                `;
            }
        });

        // Fonction pour copier le code USSD
        function copyUssdCode() {
            const ussdCommand = "{{ $ussdCommand }}";
            
            navigator.clipboard.writeText(ussdCommand).then(function() {
                const button = event.target;
                const originalText = button.innerHTML;
                button.innerHTML = '✅ Copié!';
                button.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                button.classList.add('bg-green-600');
                button.disabled = true;
                
                setTimeout(function() {
                    button.innerHTML = originalText;
                    button.classList.remove('bg-green-600');
                    button.classList.add('bg-blue-600', 'hover:bg-blue-700');
                    button.disabled = false;
                }, 2000);
            }).catch(function(err) {
                console.error('Erreur lors de la copie: ', err);
                // Fallback
                const textArea = document.createElement('textarea');
                textArea.value = ussdCommand;
                document.body.appendChild(textArea);
                textArea.select();
                try {
                    document.execCommand('copy');
                    const button = event.target;
                    const originalText = button.innerHTML;
                    button.innerHTML = '✅ Copié!';
                    button.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                    button.classList.add('bg-green-600');
                    button.disabled = true;
                    
                    setTimeout(function() {
                        button.innerHTML = originalText;
                        button.classList.remove('bg-green-600');
                        button.classList.add('bg-blue-600', 'hover:bg-blue-700');
                        button.disabled = false;
                    }, 2000);
                } catch (fallbackErr) {
                    alert('Erreur lors de la copie. Veuillez copier manuellement le code.');
                }
                document.body.removeChild(textArea);
            });
        }
    </script>
</body>
</html>
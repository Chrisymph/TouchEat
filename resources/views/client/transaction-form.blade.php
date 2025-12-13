<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation Paiement - Table {{ $tableNumber }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #ff9b4a 15%, #ff6a00 75%);
        }
        body {
        background: linear-gradient(180deg, #fbefe9 0%, #f9eae4 100%);
        font-family: 'Poppins', sans-serif;
        color: #2b2b2b;
    }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header identique à ussd-command -->
    <div class="gradient-bg text-white">
        <div class="container mx-auto px-4 py-6">
            <div class="text-center">
                <h1 class="text-3xl font-bold mb-2">Confirmation de Paiement</h1>
                <p class="text-lg">
                    Commande #{{ $order->id }} - 
                    {{ number_format($amountToPay, 0, ',', ' ') }} FCFA
                    @if(isset($paymentId))
                        <span class="text-yellow-300">(Paiement additionnel)</span>
                    @endif
                </p>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-md mx-auto">
            <!-- Carte de confirmation -->
            <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">✅ Vérification de Paiement</h2>
                
                @if(isset($paymentId))
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <div class="flex items-center">
                        <div class="text-blue-600 mr-2">ℹ️</div>
                        <div class="text-sm text-blue-700">
                            <strong>Paiement additionnel :</strong> Vous payez seulement le montant des nouveaux articles ajoutés à votre commande existante.
                        </div>
                    </div>
                </div>
                @endif
                
                <p class="text-gray-600 mb-4">Veuillez saisir les informations exactes de votre transaction :</p>
                
                <form id="transactionForm">
                    @csrf
                    
                    <div class="space-y-4">
                        <!-- Réseau -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Réseau Mobile Money *
                            </label>
                            <select name="network" required
                                    class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-orange-400">
                                <option value="">Choisissez votre réseau...</option>
                                <option value="mtn">MTN Money</option>
                                <option value="moov">Moov Money</option>
                                <option value="celtis">Celtis Cash</option>
                            </select>
                        </div>

                        <!-- Téléphone -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Numéro utilisé pour le paiement *
                            </label>
                            <input type="tel" name="phone_number" required
                                   placeholder="ex: 01 67 12 56 78"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-orange-400">
                            <p class="text-xs text-gray-500 mt-1">Doit correspondre au numéro utilisé pour le paiement</p>
                        </div>

                        <!-- ID Transaction -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                ID de Transaction *
                            </label>
                            <input type="text" name="transaction_id" required
                                   placeholder="Ex: 012569878269"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-orange-400">
                            <p class="text-sm text-gray-500 mt-1">
                                Trouvez cet ID dans le SMS de confirmation reçu
                            </p>
                        </div>
                    </div>

                    <!-- Montant à vérifier -->
                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-200 mt-4">
                        <div class="flex justify-between items-center text-lg font-semibold">
                            <span class="text-blue-700">
                                @if(isset($paymentId))
                                    Montant des nouveaux articles:
                                @else
                                    Montant à vérifier:
                                @endif
                            </span>
                            <span class="text-blue-800">{{ number_format($amountToPay, 0, ',', ' ') }} FCFA</span>
                        </div>
                        @if(isset($paymentId))
                        <p class="text-xs text-blue-600 mt-2 text-center">
                            ⚠️ Ce montant correspond seulement aux nouveaux articles ajoutés
                        </p>
                        @endif
                    </div>

                    <!-- Message d'information important -->
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-4">
                        <div class="flex items-start">
                            <div class="text-yellow-600 mr-2">⚠️</div>
                            <div class="text-sm text-yellow-700">
                                <strong>Important :</strong> La vérification échouera si :
                                <ul class="list-disc list-inside mt-1 space-y-1">
                                    <li>L'ID de transaction est incorrect</li>
                                    <li>Le SMS de confirmation n'est pas encore arrivé</li>
                                    <li>Le montant ne correspond pas</li>
                                    <li>Le numéro de téléphone est différent</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex gap-3">
                        @if(isset($paymentId))
                            <a href="{{ route('client.order.ussd.payment', ['order' => $order->id, 'payment' => $paymentId]) }}" 
                               class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-3 rounded-lg font-semibold text-center transition-colors">
                                Retour
                            </a>
                        @else
                            <a href="{{ route('client.order.ussd', $order->id) }}" 
                               class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-3 rounded-lg font-semibold text-center transition-colors">
                                Retour
                            </a>
                        @endif
                        <button type="submit" 
                                class="flex-1 bg-orange-600 hover:bg-orange-700 text-white py-3 rounded-lg font-semibold transition-colors">
                            Vérifier le Paiement
                        </button>
                    </div>
                </form>
            </div>

            <!-- Instructions -->
            <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4">
                <h3 class="font-semibold text-blue-800 mb-2">📋 Où trouver l'ID de Transaction ?</h3>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li>• Dans le SMS de confirmation de paiement</li>
                    <li>• Cherchez un code comme <strong>012569878269</strong> ou <strong>REF789ABC</strong></li>
                    <li>• Généralement 8-12 caractères (chiffres et lettres)</li>
                    <li>• <strong>Attendez que le SMS arrive</strong> avant de saisir</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('transactionForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            
            submitButton.textContent = 'Vérification en cours...';
            submitButton.disabled = true;

            try {
                @if(isset($paymentId))
                    const url = '{{ route("client.payment.process.payment", ["order" => $order->id, "payment" => $paymentId]) }}';
                @else
                    const url = '{{ route("client.payment.process", $order->id) }}';
                @endif

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    // Succès
                    showMessage('✅ ' + data.message, 'success');
                    setTimeout(() => {
                        window.location.href = data.redirect_url;
                    }, 2000);
                } else {
                    // Échec
                    showMessage('❌ ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Erreur:', error);
                showMessage('❌ Erreur lors de la vérification', 'error');
            } finally {
                submitButton.textContent = originalText;
                submitButton.disabled = false;
            }
        });

        function showMessage(message, type) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white font-semibold z-50 ${
                type === 'success' ? 'bg-green-500' : 'bg-red-500'
            }`;
            messageDiv.textContent = message;
            document.body.appendChild(messageDiv);

            setTimeout(() => {
                messageDiv.remove();
            }, 5000);
        }
    </script>
</body>
</html>
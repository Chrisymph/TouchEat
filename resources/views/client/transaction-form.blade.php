<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation Paiement - Table {{ $tableNumber }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header identique à ussd-command -->
    <div class="gradient-bg text-white">
        <div class="container mx-auto px-4 py-6">
            <div class="text-center">
                <h1 class="text-3xl font-bold mb-2">Confirmation de Paiement</h1>
                <p class="text-lg">Commande #{{ $order->id }} - {{ number_format($order->total, 0, ',', ' ') }} FCFA</p>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-md mx-auto">
            <!-- Carte de confirmation -->
            <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">✅ Paiement Effectué</h2>
                <p class="text-gray-600 mb-4">Veuillez saisir les informations de votre transaction :</p>
                
                <form id="transactionForm">
                    @csrf
                    
                    <div class="space-y-4">
                        <!-- Réseau -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Réseau Mobile Money *
                            </label>
                            <select name="network" required
                                    class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Choisissez...</option>
                                <option value="mtn">MTN Money</option>
                                <option value="moov">Moov Money</option>
                                <option value="orange">Orange Money</option>
                            </select>
                        </div>

                        <!-- Téléphone -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Numéro utilisé *
                            </label>
                            <input type="tel" name="phone_number" required
                                   placeholder="ex: 07 12 34 56 78"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- ID Transaction -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                ID de Transaction *
                            </label>
                            <input type="text" name="transaction_id" required
                                   placeholder="Ex: TX123456ABC"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p class="text-sm text-gray-500 mt-1">
                                Trouvez cet ID dans le SMS de confirmation
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 flex gap-3">
                        <a href="{{ route('client.order.ussd', $order->id) }}" 
                           class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-3 rounded-lg font-semibold text-center transition-colors">
                            Retour
                        </a>
                        <button type="submit" 
                                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-semibold transition-colors">
                            Confirmer
                        </button>
                    </div>
                </form>
            </div>

            <!-- Instructions -->
            <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4">
                <h3 class="font-semibold text-blue-800 mb-2">📋 Où trouver l'ID de Transaction ?</h3>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li>• Dans le SMS de confirmation de paiement</li>
                    <li>• Cherchez un code comme <strong>TX123456</strong> ou <strong>REF789ABC</strong></li>
                    <li>• Généralement 8-12 caractères (chiffres et lettres)</li>
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
            
            submitButton.textContent = 'Traitement...';
            submitButton.disabled = true;

            try {
                const response = await fetch('{{ route("client.payment.process", $order->id) }}', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    if (data.auto_verified) {
                        alert('✅ Paiement vérifié! Votre commande est en préparation.');
                    } else {
                        alert('⏳ Paiement enregistré! Vérification en cours...');
                    }
                    
                    window.location.href = data.redirect_url;
                } else {
                    alert('❌ ' + data.message);
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('❌ Erreur lors du traitement');
            } finally {
                submitButton.textContent = originalText;
                submitButton.disabled = false;
            }
        });
    </script>
</body>
</html>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Paiement - Table {{ $tableNumber }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
<body class="min-h-screen">

    <header class="gradient-bg text-white">
        <div class="max-w-7xl mx-auto px-4 py-6 text-center">
            <h1 class="text-2xl sm:text-3xl font-bold mb-2">Paiement Mobile Money</h1>
            <div class="flex flex-wrap justify-center items-center gap-x-3 text-sm sm:text-base">
                <span>Table N°{{ $tableNumber }}</span>
                <span class="text-white/70">•</span>
                <span>Commande #{{ $order->id }}</span>
                @if($isAdditional)
                    <span class="text-yellow-200 font-semibold">• Paiement additionnel</span>
                @endif
            </div>
        </div>
    </header>

    <main class="max-w-md mx-auto px-4 py-8">
        <section class="bg-white rounded-2xl shadow-lg p-6 text-center">

            <div class="text-5xl mb-4" aria-hidden="true">💳</div>
            <h2 class="text-xl font-bold text-gray-800 mb-2">Paiement sécurisé</h2>
            <p class="text-sm text-gray-600 mb-6">
                Payez avec MTN Money, Moov Money ou Celtiis Cash via KKiaPay.
            </p>

            <div class="bg-gray-50 rounded-xl p-4 mb-6">
                <div class="flex justify-between items-center">
                    <span class="font-semibold text-gray-700">
                        {{ $isAdditional ? 'Nouveaux articles :' : 'Total à payer :' }}
                    </span>
                    <span class="font-bold text-2xl text-green-600">
                        {{ number_format($amountToPay, 0, ',', ' ') }} FCFA
                    </span>
                </div>
                @if($isAdditional)
                    <p class="text-xs text-gray-500 mt-2">
                        ⚠️ Seul le montant des nouveaux articles est demandé.
                    </p>
                @endif
            </div>

            <button id="payBtn" type="button"
                    class="w-full bg-orange-600 hover:bg-orange-700 text-white py-4 rounded-lg font-semibold transition-all duration-300 shadow-md hover:shadow-lg">
                Payer maintenant
            </button>

            <button id="retryBtn" type="button"
                    class="hidden w-full mt-3 bg-gray-700 hover:bg-gray-800 text-white py-3 rounded-lg font-semibold transition-all duration-300">
                J'ai déjà payé : revérifier
            </button>

            <p id="statusMsg" role="status" aria-live="polite" class="hidden mt-4 text-sm font-medium"></p>

            <a href="{{ route('client.dashboard') }}"
               class="inline-block mt-6 text-gray-600 hover:text-orange-600 text-sm font-semibold">
                ← Retour à l'accueil
            </a>
        </section>
    </main>

    <script src="https://cdn.kkiapay.me/k.js"></script>
    <script>
        (function () {
            const VERIFY_URL = @json(route('client.kkiapay.verify', ['order' => $order->id, 'payment' => $payment->id]));
            const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            const payBtn = document.getElementById('payBtn');
            const retryBtn = document.getElementById('retryBtn');
            const statusMsg = document.getElementById('statusMsg');

            let lastTransactionId = null;
            let verifying = false;

            function showStatus(message, type) {
                statusMsg.textContent = message;
                statusMsg.classList.remove('hidden', 'text-green-600', 'text-red-600', 'text-gray-600');
                statusMsg.classList.add(type === 'success' ? 'text-green-600' : (type === 'error' ? 'text-red-600' : 'text-gray-600'));
            }

            function openWidget() {
                openKkiapayWidget({
                    amount: {{ (int) round($amountToPay) }},
                    api_key: @json(config('services.kkiapay.public_key')),
                    sandbox: {{ config('services.kkiapay.sandbox') ? 'true' : 'false' }},
                    phone: @json(preg_replace('/\D+/', '', (string) $order->customer_phone)),
                    data: @json((string) $payment->id),
                    theme: '#ff6a00',
                    position: 'center'
                });
            }

            async function verifyPayment(transactionId) {
                if (verifying) return;
                verifying = true;
                payBtn.disabled = true;
                retryBtn.classList.add('hidden');
                showStatus('Vérification du paiement en cours...', 'info');

                try {
                    const response = await fetch(VERIFY_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': CSRF
                        },
                        body: JSON.stringify({ transaction_id: transactionId })
                    });

                    const data = await response.json();

                    if (data.success) {
                        showStatus('✅ ' + data.message, 'success');
                        setTimeout(() => { window.location.href = data.redirect_url; }, 1500);
                        return;
                    }

                    showStatus('❌ ' + (data.message || 'Paiement non confirmé.'), 'error');
                    retryBtn.classList.remove('hidden');
                } catch (e) {
                    console.error(e);
                    showStatus('❌ Erreur réseau pendant la vérification.', 'error');
                    retryBtn.classList.remove('hidden');
                } finally {
                    verifying = false;
                    payBtn.disabled = false;
                }
            }

            addSuccessListener(function (response) {
                lastTransactionId = response.transactionId;
                verifyPayment(lastTransactionId);
            });

            addFailedListener(function () {
                showStatus('Le paiement a échoué ou a été annulé. Vous pouvez réessayer.', 'error');
            });

            payBtn.addEventListener('click', openWidget);
            retryBtn.addEventListener('click', function () {
                if (lastTransactionId) verifyPayment(lastTransactionId);
            });

            // Ouverture automatique à l'arrivée sur la page
            window.addEventListener('load', function () { setTimeout(openWidget, 400); });
        })();
    </script>
</body>
</html>
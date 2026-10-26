
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Commande USSD - Table {{ $tableNumber }}</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"></script>

    <style>
        * {
            box-sizing: border-box;
        }

        html {
            -webkit-text-size-adjust: 100%;
        }

        body {
            background: linear-gradient(180deg, #fbefe9 0%, #f9eae4 100%);
            font-family: Arial, sans-serif;
            color: #2b2b2b;
            overflow-x: hidden;
        }

        .gradient-bg {
            background: linear-gradient(135deg, #ff9b4a 15%, #ff6a00 75%);
        }

        .ussd-code {
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
        }

        #ussdCommand {
            overflow-wrap: anywhere;
            word-break: break-word;
            white-space: pre-wrap;
        }

        #qrcode canvas {
            display: block;
            width: 100%;
            max-width: 200px;
            height: auto;
            margin: 0 auto;
            image-rendering: pixelated;
        }

        #qrcode {
            max-width: 100%;
        }

        .copy-button {
            flex: 0 0 auto;
            min-width: 44px;
            min-height: 44px;
            touch-action: manipulation;
        }

        .action-button {
            width: 100%;
            max-width: 100%;
            overflow-wrap: anywhere;
        }

        @media (min-width: 640px) {
            .action-button {
                width: auto;
            }
        }
    </style>
</head>

<body class="min-h-screen">

    <!-- En-tête -->
    <header class="gradient-bg text-white">
        <div class="max-w-7xl mx-auto px-3 sm:px-4 py-4 sm:py-6">

            <div class="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center">

                <div class="text-center flex-1 min-w-0">
                    <h1 class="text-2xl sm:text-3xl font-bold mb-2">
                        Paiement Mobile Money
                    </h1>

                    <div class="flex flex-wrap justify-center items-center gap-x-3 gap-y-1 text-sm sm:text-base">
                        <span>Table N°{{ $tableNumber }}</span>
                        <span class="text-white/70">•</span>
                        <span class="text-white/90">
                            Commande #{{ $order->id }}
                        </span>

                        @if(isset($paymentId))
                            <span class="text-yellow-200 font-semibold">
                                • Paiement additionnel
                            </span>
                        @endif
                    </div>
                </div>

                <a href="{{ route('client.dashboard') }}"
                   class="inline-flex items-center justify-center gap-2
                          bg-white/20 hover:bg-white/30 text-white
                          px-5 py-3 rounded-lg font-semibold
                          transition-colors backdrop-blur-sm
                          w-full sm:w-auto">

                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="w-5 h-5"
                         viewBox="0 0 24 24"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="2">
                        <path d="M19 12H5"/>
                        <path d="m12 19-7-7 7-7"/>
                    </svg>

                    Retour
                </a>

            </div>
        </div>
    </header>

    <!-- Contenu principal -->
    <main class="max-w-7xl mx-auto px-3 sm:px-4 py-5 sm:py-8">

        <div class="max-w-2xl mx-auto min-w-0">

            <!-- Carte de confirmation -->
            <section class="bg-white rounded-2xl shadow-lg
                            p-4 sm:p-6 md:p-8 mb-6 sm:mb-8
                            border border-green-200">

                <div class="text-center mb-6">
                    <div class="text-5xl sm:text-6xl mb-4" aria-hidden="true">
                        ✅
                    </div>

                    <h2 class="text-xl sm:text-2xl font-bold text-gray-800 mb-2">
                        @if(isset($paymentId))
                            Articles ajoutés à la commande !
                        @else
                            Commande confirmée !
                        @endif
                    </h2>

                    <p class="text-sm sm:text-base text-gray-600">
                        @if(isset($paymentId))
                            Vos nouveaux articles ont été ajoutés à la commande #{{ $order->id }}.
                        @else
                            Votre commande #{{ $order->id }} a été enregistrée avec succès.
                        @endif
                    </p>
                </div>

                <!-- Montant -->
                <div class="bg-gray-50 rounded-xl p-4 sm:p-6 mb-2">
                    <div class="flex flex-col sm:flex-row
                                sm:justify-between sm:items-center gap-2">

                        <span class="font-semibold text-gray-700 text-sm sm:text-base">
                            @if(isset($paymentId))
                                Montant des nouveaux articles :
                            @else
                                Total à payer :
                            @endif
                        </span>

                        <span class="font-bold text-xl sm:text-2xl text-green-600">
                            {{ number_format($amountToPay, 0, ',', ' ') }} FCFA
                        </span>

                    </div>

                    @if(isset($paymentId))
                        <p class="text-sm text-gray-500 mt-3 text-center">
                            ⚠️ Seul le montant des nouveaux articles est demandé pour ce paiement.
                        </p>
                    @endif
                </div>
            </section>

            <!-- Instructions de paiement -->
            <section class="bg-white rounded-2xl shadow-lg
                            p-4 sm:p-6 md:p-8 mb-6 sm:mb-8">

                <h3 class="text-xl sm:text-2xl font-bold text-gray-800 mb-6 text-center">
                    Instructions de paiement

                    <span class="block mt-2 text-orange-600">
                        @if($selectedNetwork === 'mtn')
                            MTN Money
                        @elseif($selectedNetwork === 'moov')
                            Moov Money
                        @else
                            Celtiis Bénin
                        @endif
                    </span>
                </h3>

                <!-- Commande USSD avec icône de copie -->
                <div class="mb-8 w-full min-w-0">

                    <div class="flex items-center justify-between gap-2 mb-3">
                        <h4 class="text-base sm:text-lg font-semibold text-gray-800">
                            Votre commande USSD
                        </h4>

                        <span class="text-xs sm:text-sm text-gray-500 flex-shrink-0">
                            À copier
                        </span>
                    </div>

                    <div class="ussd-code flex items-center gap-2 sm:gap-3
                                w-full min-w-0 p-3 sm:p-4 rounded-xl">

                        <code id="ussdCommand"
                              class="flex-1 min-w-0 text-sm sm:text-base
                                     md:text-lg font-bold text-gray-800
                                     leading-relaxed select-all">{{ $ussdCommand }}</code>

                        <button
                            type="button"
                            id="copyUssdButton"
                            aria-label="Copier la commande USSD"
                            title="Copier la commande USSD"
                            class="copy-button inline-flex items-center justify-center
                                   rounded-lg bg-orange-600 text-white
                                   hover:bg-orange-700 active:scale-95
                                   transition-all duration-200
                                   focus:outline-none focus:ring-2
                                   focus:ring-orange-400 focus:ring-offset-2">

                            <!-- Icône de copie -->
                            <svg id="copyUssdIcon"
                                 xmlns="http://www.w3.org/2000/svg"
                                 class="w-5 h-5"
                                 fill="none"
                                 viewBox="0 0 24 24"
                                 stroke="currentColor"
                                 stroke-width="2"
                                 aria-hidden="true">
                                <rect x="8" y="8" width="12" height="12" rx="2"/>
                                <path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3"/>
                            </svg>

                        </button>
                    </div>

                    <!-- Message de copie -->
                    <p id="copyUssdMessage"
                       role="status"
                       aria-live="polite"
                       class="hidden mt-3 text-sm text-center">
                    </p>

                    <p class="text-gray-600 text-sm mt-3 text-center">
                        Appuyez sur l'icône pour copier la commande, puis collez-la
                        dans l'application Téléphone.
                    </p>
                </div>

                <!-- QR Code -->
                <div class="text-center border-t border-gray-200 pt-6 sm:pt-8">

                    <h4 class="text-base sm:text-lg font-semibold text-gray-800 mb-4">
                        📱 Ou scannez le QR Code
                    </h4>

                    <div class="flex justify-center mb-4 min-w-0">
                        <div id="qrcode"
                             class="p-3 sm:p-4 bg-white rounded-lg
                                    border border-gray-200 inline-block"
                             role="img"
                             aria-label="QR Code contenant la commande USSD">
                        </div>
                    </div>

                    <p class="text-gray-600 mt-4 text-sm">
                        Scannez ce code avec le scanner QR de votre téléphone
                        pour extraire la commande USSD.
                    </p>
                </div>
            </section>

            <!-- Instructions étape par étape -->
            <section class="bg-white rounded-2xl shadow-lg
                            p-4 sm:p-6 md:p-8">

                <h4 class="text-lg sm:text-xl font-bold text-gray-800 mb-5">
                    Comment procéder ?
                </h4>

                <div class="space-y-5">

                    <div class="flex items-start gap-3">
                        <span class="bg-orange-600 text-white rounded-full
                                     min-w-6 w-6 h-6 flex items-center
                                     justify-center text-sm font-bold mt-0.5">
                            1
                        </span>

                        <p class="text-gray-700 text-sm sm:text-base">
                            Ouvrez l'application Téléphone sur votre appareil Android.
                        </p>
                    </div>

                    <div class="flex items-start gap-3">
                        <span class="bg-orange-600 text-white rounded-full
                                     min-w-6 w-6 h-6 flex items-center
                                     justify-center text-sm font-bold mt-0.5">
                            2
                        </span>

                        <p class="text-gray-700 text-sm sm:text-base">
                            Copiez la commande USSD à l'aide de l'icône ci-dessus.
                            Vous pouvez également scanner le QR Code avec le scanner
                            intégré à votre téléphone, puis copier le texte obtenu
                            dans l'application Téléphone.
                        </p>
                    </div>

                    <div class="flex items-start gap-3">
                        <span class="bg-orange-600 text-white rounded-full
                                     min-w-6 w-6 h-6 flex items-center
                                     justify-center text-sm font-bold mt-0.5">
                            3
                        </span>

                        <p class="text-gray-700 text-sm sm:text-base">
                            Suivez les instructions affichées par votre opérateur
                            et confirmez la transaction avec votre code PIN.
                        </p>
                    </div>

                    <div class="flex items-start gap-3">
                        <span class="bg-orange-600 text-white rounded-full
                                     min-w-6 w-6 h-6 flex items-center
                                     justify-center text-sm font-bold mt-0.5">
                            4
                        </span>

                        <p class="text-gray-700 text-sm sm:text-base">
                            Une fois le paiement effectué, revenez sur cette page
                            pour saisir l'identifiant de transaction.
                        </p>
                    </div>

                </div>

                <!-- Note importante -->
                <div class="mt-6 flex items-start gap-3 rounded-xl
                            border border-amber-300 bg-amber-50 p-3 sm:p-4">

                    <span class="text-amber-600 text-xl flex-shrink-0"
                          aria-hidden="true">
                        ⚠️
                    </span>

                    <p class="text-sm text-gray-700">
                        <strong class="text-amber-800">NB :</strong>
                        Ce code QR ne peut pas être scanné directement avec
                        la fonctionnalité de scan QR des applications Mobile Money
                        (MoMo) des opérateurs. Utilisez plutôt le scanner QR intégré
                        à votre téléphone pour extraire la commande USSD, puis
                        copiez-la dans l'application Téléphone.
                    </p>
                </div>

            </section>

            <!-- Actions après paiement -->
            <div class="text-center mt-6 sm:mt-8 space-y-4">

                @if(isset($paymentId))
                    <a href="{{ route('client.payment.form.payment', ['order' => $order->id, 'payment' => $paymentId]) }}"
                       class="action-button inline-flex items-center justify-center
                              gap-2 bg-orange-600 hover:bg-orange-700
                              text-white px-4 sm:px-8 py-3 sm:py-4
                              rounded-lg font-semibold transition-all
                              duration-300 shadow-md hover:shadow-lg">

                        <span aria-hidden="true">✅</span>
                        <span>J'ai payé - Saisir l'ID de transaction</span>
                    </a>
                @else
                    <a href="{{ route('client.payment.form', $order->id) }}"
                       class="action-button inline-flex items-center justify-center
                              gap-2 bg-orange-600 hover:bg-orange-700
                              text-white px-4 sm:px-8 py-3 sm:py-4
                              rounded-lg font-semibold transition-all
                              duration-300 shadow-md hover:shadow-lg">

                        <span aria-hidden="true">✅</span>
                        <span>J'ai payé - Saisir l'ID de transaction</span>
                    </a>
                @endif

                <div>
                    <a href="{{ route('client.dashboard') }}"
                       class="action-button inline-flex items-center justify-center
                              bg-gray-600 hover:bg-gray-700 text-white
                              px-6 py-3 rounded-lg font-semibold
                              transition-all duration-300">

                        ← Retour à l'accueil
                    </a>
                </div>

            </div>

        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // Récupérer la commande depuis le contenu HTML échappé par Blade.
            const commandElement = document.getElementById('ussdCommand');
            const qrcodeElement = document.getElementById('qrcode');

            const copyButton = document.getElementById('copyUssdButton');
            const copyMessage = document.getElementById('copyUssdMessage');
            const copyIcon = document.getElementById('copyUssdIcon');

            const ussdCommand = commandElement.textContent.trim();

            // Génération du QR Code
            try {
                if (typeof qrcode !== 'function') {
                    throw new Error('La bibliothèque QR Code est indisponible.');
                }

                const qr = qrcode(0, 'M');
                qr.addData(ussdCommand);
                qr.make();

                // Utiliser l'image générée par la bibliothèque.
                qrcodeElement.innerHTML = qr.createImgTag(5, 4);

                const qrImage = qrcodeElement.querySelector('img');

                if (qrImage) {
                    qrImage.alt = 'QR Code de la commande USSD';
                    qrImage.classList.add('mx-auto');
                    qrImage.style.width = '100%';
                    qrImage.style.maxWidth = '200px';
                    qrImage.style.height = 'auto';
                }

            } catch (error) {
                console.error('Erreur de génération du QR Code :', error);

                qrcodeElement.innerHTML = `
                    <div class="text-center p-4 sm:p-6">
                        <div class="text-3xl mb-2" aria-hidden="true">📱</div>
                        <p class="text-gray-600 text-sm">
                            Le QR Code n'est pas disponible.
                        </p>
                        <p class="text-gray-500 text-xs mt-2">
                            Utilisez la commande USSD affichée ci-dessus.
                        </p>
                    </div>
                `;
            }

            // Copier la commande USSD
            copyButton.addEventListener('click', async function () {

                try {
                    if (navigator.clipboard && window.isSecureContext) {
                        await navigator.clipboard.writeText(ussdCommand);
                    } else {
                        // Solution de secours pour certains navigateurs.
                        const textArea = document.createElement('textarea');

                        textArea.value = ussdCommand;
                        textArea.setAttribute('readonly', '');
                        textArea.style.position = 'fixed';
                        textArea.style.opacity = '0';
                        textArea.style.pointerEvents = 'none';
                        textArea.style.left = '0';
                        textArea.style.top = '0';

                        document.body.appendChild(textArea);
                        textArea.focus();
                        textArea.select();
                        textArea.setSelectionRange(0, textArea.value.length);

                        let copied = false;

                        try {
                            copied = document.execCommand('copy');
                        } finally {
                            textArea.remove();
                        }

                        if (!copied) {
                            throw new Error('La copie automatique a échoué.');
                        }
                    }

                    // Afficher le succès
                    copyMessage.textContent = '✓ Commande copiée avec succès !';
                    copyMessage.classList.remove('hidden', 'text-red-600');
                    copyMessage.classList.add('text-green-600');

                    copyButton.classList.remove('bg-orange-600', 'hover:bg-orange-700');
                    copyButton.classList.add('bg-green-600');

                    copyButton.setAttribute('aria-label', 'Commande copiée');

                    // Remplacer l'icône de copie par une coche.
                    copyIcon.innerHTML = '<path d="m5 12 4 4L19 6"/>';

                } catch (error) {
                    console.error('Erreur de copie :', error);

                    copyMessage.textContent =
                        'Copie impossible. Maintenez la commande appuyée pour la sélectionner manuellement.';

                    copyMessage.classList.remove('hidden', 'text-green-600');
                    copyMessage.classList.add('text-red-600');

                }
            });

        });
    </script>

</body>
</html>

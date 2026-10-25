<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Merci de votre visite - Touch & Eat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #ff9500, #ffcc00);
            min-height: 100vh;
            position: relative;
            overflow: hidden;
        }
        body::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 20% 80%, rgba(255,255,255,0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255,255,255,0.15) 0%, transparent 50%);
            pointer-events: none;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }
        .float-anim { animation: float 3s ease-in-out infinite; }
        .float-anim-delay { animation: float 3s ease-in-out infinite; animation-delay: 1.5s; }
    </style>
</head>
<body class="flex items-center justify-center p-4 relative">

    <!-- Icônes décoratives -->
    <div class="absolute top-10 left-10 text-6xl opacity-30 float-anim">🍔</div>
    <div class="absolute top-20 right-10 text-6xl opacity-30 float-anim-delay">🍕</div>
    <div class="absolute bottom-10 left-20 text-6xl opacity-30 float-anim-delay">🥤</div>
    <div class="absolute bottom-20 right-20 text-6xl opacity-30 float-anim">🍰</div>

    <!-- Contenu principal -->
    <div class="relative z-10 bg-white rounded-3xl shadow-2xl p-8 md:p-12 max-w-2xl w-full text-center">

        <div class="text-8xl md:text-9xl mb-6 float-anim">🙏</div>

        <h1 class="text-4xl md:text-5xl font-extrabold text-gray-800 mb-4 leading-tight">
            Merci de votre visite !
        </h1>

        <p class="text-lg md:text-xl text-gray-600 mb-8 leading-relaxed">
            Nous espérons que vous avez passé un excellent moment à notre table.<br class="hidden md:inline">
            À bientôt et bonne journée ! 🍽️
        </p>

        <!-- Bloc info -->
        <div class="bg-orange-50 rounded-2xl p-6 mb-8 border-2 border-orange-200 text-left">
            <p class="text-lg text-gray-800 font-semibold mb-2 flex items-center gap-2">
                💡 Pour commander à nouveau
            </p>
            <p class="text-gray-600">
                Scannez simplement le QR code de votre table avec votre téléphone.
                Votre commande vous sera servie dans les meilleurs délais.
            </p>
        </div>

        <!-- Signature -->
        <div class="text-gray-500 text-sm border-t border-gray-200 pt-6">
            <p class="font-bold text-orange-600 text-lg mb-1">Touch & Eat</p>
            <p>Votre expérience culinaire digitale</p>
        </div>

    </div>

</body>
</html>
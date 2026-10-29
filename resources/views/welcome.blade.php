
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Touch Eat - Restaurant Digital</title>

    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@800;900&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            min-height: 100%;
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background: linear-gradient(135deg, #ff9500, #ffcc00);
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
            overflow-y: auto;
            padding: 30px 16px;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(circle at 20% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(255, 255, 255, 0.05) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }

        .container {
            width: 100%;
            max-width: 650px;
            text-align: center;
            position: relative;
            z-index: 10;
            padding: 20px 12px;
            margin: auto;
        }

        .logo-container {
            margin-bottom: 2rem;
            display: flex;
            justify-content: center;
            align-items: center;
            animation: float 3s ease-in-out infinite;
        }

        .logo-img {
            display: block;
            max-width: 300px;
            width: 100%;
            height: auto;
            max-height: 35vh;
            object-fit: contain;
            border-radius: 25px;
            box-shadow:
                4px 4px 0 rgba(0, 0, 0, 0.2),
                8px 8px 0 rgba(0, 0, 0, 0.1);
            background: white;
            padding: 10px;
        }

        #fallback-logo {
            display: none;
            font-family: 'Poppins', sans-serif;
            font-size: clamp(2rem, 8vw, 4rem);
            color: white;
            text-shadow: 4px 4px 0 rgba(0, 0, 0, 0.2);
            overflow-wrap: anywhere;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-8px);
            }
        }

        .subtitle {
            font-size: clamp(1rem, 3.5vw, 1.5rem);
            color: rgba(255, 255, 255, 0.95);
            margin-bottom: 2.5rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            line-height: 1.5;
        }

        .start-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: auto;
            max-width: 100%;
            min-height: 56px;
            background: linear-gradient(45deg, #ff6b00, #ff8c00);
            color: white;
            border: none;
            padding: 1rem clamp(1.5rem, 6vw, 4rem);
            font-family: inherit;
            font-size: clamp(1rem, 4vw, 1.5rem);
            font-weight: 800;
            border-radius: 50px;
            cursor: pointer;
            box-shadow:
                0 10px 30px rgba(255, 107, 0, 0.4),
                0 4px 0 rgba(255, 107, 0, 0.3);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            position: relative;
            overflow: hidden;
            touch-action: manipulation;
        }

        .start-button:hover {
            transform: translateY(-2px);
            box-shadow:
                0 15px 35px rgba(255, 107, 0, 0.45),
                0 5px 0 rgba(255, 107, 0, 0.3);
        }

        .start-button:active {
            transform: translateY(1px);
        }

        .start-button:focus-visible,
        .choice-button:focus-visible {
            outline: 3px solid white;
            outline-offset: 4px;
        }

        /* Icônes décoratives */
        .food-icons {
            position: fixed;
            font-size: 4rem;
            opacity: 0.25;
            animation: float 6s ease-in-out infinite;
            filter: drop-shadow(2px 2px 4px rgba(0, 0, 0, 0.2));
            z-index: 1;
            pointer-events: none;
            user-select: none;
        }

        .burger {
            top: 10%;
            left: 7%;
            font-size: 4.5rem;
        }

        .pizza {
            top: 12%;
            right: 8%;
            font-size: 5rem;
            animation-delay: 1.5s;
        }

        .fries {
            bottom: 20%;
            left: 8%;
            font-size: 4rem;
            animation-delay: 3s;
        }

        .drink {
            bottom: 12%;
            right: 7%;
            font-size: 4.2rem;
            animation-delay: 4.5s;
        }

        .sushi {
            top: 40%;
            right: 4%;
            font-size: 3.5rem;
            animation-delay: 2s;
        }

        .icecream {
            bottom: 38%;
            right: 15%;
            font-size: 3.8rem;
            animation-delay: 5s;
        }

        .coffee {
            top: 35%;
            left: 5%;
            font-size: 3.5rem;
            animation-delay: 1s;
        }

        .cake {
            bottom: 8%;
            left: 23%;
            font-size: 3.5rem;
            animation-delay: 3.5s;
        }

        /* Choix utilisateur */
        .choice-buttons {
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 14px;
            width: 100%;
            margin: 28px auto 0;
            animation: fadeIn 0.4s ease-out;
            scroll-margin-top: 20px;
        }

        .choice-buttons.visible {
            display: flex;
        }

        .choice-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            max-width: 340px;
            min-height: 54px;
            padding: 14px 18px;
            background: rgba(255, 255, 255, 0.22);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.65);
            border-radius: 16px;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.2s ease;
            font-family: inherit;
            font-size: 1rem;
            font-weight: 800;
            line-height: 1.4;
            overflow-wrap: anywhere;
            touch-action: manipulation;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .choice-button:hover {
            background: rgba(255, 255, 255, 0.35);
            transform: translateY(-2px);
        }

        .choice-button:active {
            transform: scale(0.98);
        }

        .choice-button.admin {
            background: #ffffff;
            color: #b94b00;
            border-color: white;
            box-shadow: 0 6px 18px rgba(120, 60, 0, 0.2);
        }

        .choice-button.admin:hover {
            background: #fff7ed;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(12px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Téléphones */
        @media (max-width: 480px) {
            body {
                align-items: flex-start;
                padding: 20px 12px 28px;
            }

            .container {
                padding: 18px 6px;
            }

            .logo-container {
                margin-bottom: 1.5rem;
            }

            .logo-img {
                max-width: min(220px, 70vw);
                max-height: 28vh;
                border-radius: 18px;
            }

            .subtitle {
                margin-bottom: 2rem;
            }

            .start-button {
                width: 100%;
                max-width: 320px;
                min-height: 54px;
            }

            .choice-buttons {
                margin-top: 22px;
            }

            .choice-button {
                max-width: 100%;
            }

            .food-icons {
                font-size: 2rem;
                opacity: 0.16;
            }

            .burger {
                top: 3%;
                left: 2%;
                font-size: 2.2rem;
            }

            .pizza {
                top: 5%;
                right: 2%;
                font-size: 2.5rem;
            }

            .fries {
                bottom: 4%;
                left: 3%;
                font-size: 2rem;
            }

            .drink {
                bottom: 4%;
                right: 3%;
                font-size: 2.1rem;
            }

            .sushi {
                top: 30%;
                right: 1%;
                font-size: 1.8rem;
            }

            .icecream {
                bottom: 30%;
                right: 3%;
                font-size: 2.1rem;
            }

            .coffee {
                top: 35%;
                left: 1%;
                font-size: 1.8rem;
            }

            .cake {
                bottom: 18%;
                left: 4%;
                font-size: 2rem;
            }
        }

        /* Écrans bas ou paysage */
        @media (max-height: 600px) {
            body {
                align-items: flex-start;
                padding-top: 12px;
                padding-bottom: 20px;
            }

            .logo-container {
                margin-bottom: 1rem;
            }

            .logo-img {
                max-height: 22vh;
                max-width: 180px;
            }

            .subtitle {
                margin-bottom: 1.25rem;
            }

            .choice-buttons {
                margin-top: 18px;
            }

            .food-icons {
                opacity: 0.12;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                scroll-behavior: auto !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>

<body>

    <!-- Icônes décoratives -->
    <div class="food-icons burger" aria-hidden="true">🍔</div>
    <div class="food-icons pizza" aria-hidden="true">🍕</div>
    <div class="food-icons fries" aria-hidden="true">🍟</div>
    <div class="food-icons drink" aria-hidden="true">🥤</div>
    <div class="food-icons sushi" aria-hidden="true">🍣</div>
    <div class="food-icons icecream" aria-hidden="true">🍦</div>
    <div class="food-icons coffee" aria-hidden="true">☕</div>
    <div class="food-icons cake" aria-hidden="true">🍰</div>

    <!-- Contenu principal -->
    <main class="container">

        <div class="logo-container">
            <img
                src="{{ asset('logo-touch-eat.png') }}"
                alt="Logo Touch Eat"
                class="logo-img"
                onerror="this.onerror=null; this.style.display='none'; document.getElementById('fallback-logo').style.display='block';"
            >

            <h1 id="fallback-logo">
                TOUCH EAT
            </h1>
        </div>

        <p class="subtitle">
            Votre expérience culinaire digitale
        </p>

        <button
            type="button"
            class="start-button"
            id="startButton"
            aria-controls="choiceButtons"
            aria-expanded="false"
            onclick="startExperience()"
        >
            Commencer
        </button>

        @guest
            <div class="choice-buttons" id="choiceButtons">
                <button
                    type="button"
                    class="choice-button admin"
                    onclick="window.location.href='{{ route('admin.auth') }}'"
                >
                    <span aria-hidden="true">👨‍💼</span>
                    <span>Espace Administrateur</span>
                </button>
            </div>
        @endguest

    </main>

    <script>
        function startExperience() {
            const button = document.getElementById('startButton');

            button.style.transform = 'scale(0.97)';

            setTimeout(() => {
                button.style.transform = '';

                @auth
                    @if(Auth::user()->role === 'admin')
                        window.location.href = @json(route('admin.dashboard'));
                    @else
                        window.location.href = @json(url('/home'));
                    @endif
                @else
                    const choiceButtons = document.getElementById('choiceButtons');

                    if (choiceButtons) {
                        choiceButtons.classList.add('visible');
                        button.setAttribute('aria-expanded', 'true');

                        // Faire apparaître le bouton dans la zone visible
                        requestAnimationFrame(() => {
                            choiceButtons.scrollIntoView({
                                behavior: window.matchMedia(
                                    '(prefers-reduced-motion: reduce)'
                                ).matches ? 'auto' : 'smooth',
                                block: 'nearest'
                            });
                        });
                    }
                @endauth
            }, 120);
        }

        // Effet de parallaxe uniquement sur les appareils à souris
        // afin de ne pas gêner les interactions tactiles.
        if (window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
            document.addEventListener('mousemove', function (event) {
                const icons = document.querySelectorAll('.food-icons');

                const mouseX = event.clientX / window.innerWidth;
                const mouseY = event.clientY / window.innerHeight;

                icons.forEach((icon, index) => {
                    const speed = (index + 1) * 0.15;
                    const x = (mouseX - 0.5) * speed * 10;
                    const y = (mouseY - 0.5) * speed * 10;

                    icon.style.transform = `translate(${x}px, ${y}px)`;
                });
            });
        }
    </script>

</body>
</html>

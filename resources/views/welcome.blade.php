<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Touch Eat - Restaurant Digital</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@800;900&display=swap" rel="stylesheet">

        <!-- Styles -->
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: 'Nunito', sans-serif;
                background: linear-gradient(135deg, #ff9500, #ffcc00);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                position: relative;
                overflow: hidden;
            }

            body::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: 
                    radial-gradient(circle at 20% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                    radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                    radial-gradient(circle at 40% 40%, rgba(255, 255, 255, 0.05) 0%, transparent 50%);
                pointer-events: none;
            }

            .container {
                text-align: center;
                z-index: 10;
                position: relative;
                padding: 2rem;
            }

            .logo-container {
                margin-bottom: 3rem;
                display: flex;
                justify-content: center;
                align-items: center;
                animation: float 3s ease-in-out infinite;
            }

            .logo-img {
                max-width: 300px;
                width: 100%;
                height: auto;
                border-radius: 25px; /* Bords arrondis */
                box-shadow: 
                    4px 4px 0px rgba(0, 0, 0, 0.2),
                    8px 8px 0px rgba(0, 0, 0, 0.1);
                background: white;
                padding: 10px;
            }

            @keyframes float {
                0%, 100% {
                    transform: translateY(0px);
                }
                50% {
                    transform: translateY(-10px);
                }
            }

            .subtitle {
                font-size: 1.5rem;
                color: rgba(255, 255, 255, 0.9);
                margin-bottom: 4rem;
                font-weight: 300;
                letter-spacing: 1px;
            }

            .start-button {
                background: linear-gradient(45deg, #ff6b00, #ff8c00);
                color: white;
                border: none;
                padding: 1.5rem 4rem;
                font-size: 1.5rem;
                font-weight: 700;
                border-radius: 50px;
                cursor: pointer;
                box-shadow: 
                    0 10px 30px rgba(255, 107, 0, 0.4),
                    0 4px 0 rgba(255, 107, 0, 0.3);
                transition: all 0.3s ease;
                text-transform: uppercase;
                letter-spacing: 2px;
                position: relative;
                overflow: hidden;
            }

            .start-button::before {
                content: '';
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
                transition: left 0.5s;
            }

            .start-button:hover {
                transform: translateY(-3px);
                box-shadow: 
                    0 15px 40px rgba(255, 107, 0, 0.6),
                    0 6px 0 rgba(255, 107, 0, 0.3);
            }

            .start-button:hover::before {
                left: 100%;
            }

            .start-button:active {
                transform: translateY(1px);
                box-shadow: 
                    0 5px 20px rgba(255, 107, 0, 0.4),
                    0 2px 0 rgba(255, 107, 0, 0.3);
            }

            /* ICÔNES DE NOURRITURE AMÉLIORÉES */
            .food-icons {
                position: absolute;
                font-size: 4rem;
                opacity: 0.3; /* Augmenté l'opacité */
                animation: float 6s ease-in-out infinite;
                filter: drop-shadow(2px 2px 4px rgba(0, 0, 0, 0.2)); /* Ombre pour meilleur contraste */
                z-index: 5;
            }

            .burger { 
                top: 10%; 
                left: 10%; 
                animation-delay: 0s; 
                font-size: 4.5rem;
            }
            .pizza { 
                top: 15%; 
                right: 12%; 
                animation-delay: 1.5s; 
                font-size: 5rem;
            }
            .fries { 
                bottom: 25%; 
                left: 12%; 
                animation-delay: 3s; 
                font-size: 4rem;
            }
            .drink { 
                bottom: 15%; 
                right: 10%; 
                animation-delay: 4.5s; 
                font-size: 4.2rem;
            }
            /* Nouvelles icônes ajoutées */
            .sushi {
                top: 8%;
                right: 20%;
                animation-delay: 2s;
                font-size: 3.8rem;
            }
            .icecream {
                bottom: 30%;
                right: 25%;
                animation-delay: 5s;
                font-size: 4.3rem;
            }
            .coffee {
                top: 25%;
                left: 5%;
                animation-delay: 6s;
                font-size: 3.5rem;
            }
            .cake {
                bottom: 10%;
                left: 20%;
                animation-delay: 7s;
                font-size: 4rem;
            }

            /* Responsive */
            @media (max-width: 768px) {
                .logo-img {
                    max-width: 250px;
                    border-radius: 20px;
                }
                
                .subtitle {
                    font-size: 1.2rem;
                }
                
                .start-button {
                    padding: 1.2rem 3rem;
                    font-size: 1.2rem;
                }
                
                .food-icons {
                    font-size: 2.5rem;
                }
                
                .burger { font-size: 3rem; }
                .pizza { font-size: 3.5rem; }
                .fries { font-size: 2.8rem; }
                .drink { font-size: 3rem; }
                .sushi { font-size: 2.8rem; }
                .icecream { font-size: 3rem; }
                .coffee { font-size: 2.5rem; }
                .cake { font-size: 3rem; }
            }

            @media (max-width: 480px) {
                .logo-img {
                    max-width: 200px;
                    border-radius: 15px;
                }
                
                .subtitle {
                    font-size: 1rem;
                    margin-bottom: 3rem;
                }
                
                .start-button {
                    padding: 1rem 2.5rem;
                    font-size: 1.1rem;
                }
                
                .food-icons {
                    font-size: 2rem;
                    opacity: 0.25;
                }
                
                .burger { font-size: 2.2rem; }
                .pizza { font-size: 2.5rem; }
                .fries { font-size: 2rem; }
                .drink { font-size: 2.1rem; }
                .sushi { font-size: 1.8rem; }
                .icecream { font-size: 2.1rem; }
                .coffee { font-size: 1.8rem; }
                .cake { font-size: 2rem; }
            }

            /* Navigation discrète */
            .auth-links {
                position: absolute;
                top: 2rem;
                right: 2rem;
                z-index: 20;
            }

            .auth-link {
                color: rgba(255, 255, 255, 0.8);
                text-decoration: none;
                font-size: 0.9rem;
                margin-left: 1rem;
                padding: 0.5rem 1rem;
                border: 1px solid rgba(255, 255, 255, 0.3);
                border-radius: 20px;
                transition: all 0.3s ease;
            }

            .auth-link:hover {
                background: rgba(255, 255, 255, 0.1);
                color: white;
            }

            .choice-buttons {
                display: flex;
                gap: 1rem;
                justify-content: center;
                margin-top: 2rem;
            }

            .choice-button {
                background: rgba(255, 255, 255, 0.2);
                color: white;
                border: 2px solid rgba(255, 255, 255, 0.3);
                padding: 0.8rem 1.5rem;
                border-radius: 25px;
                cursor: pointer;
                transition: all 0.3s ease;
                font-weight: 600;
                backdrop-filter: blur(10px);
            }

            .choice-button:hover {
                background: rgba(255, 255, 255, 0.3);
                transform: translateY(-2px);
            }
        </style>
    </head>
    <body>

        <!-- ICÔNES DE NOURRITURE AMÉLIORÉES -->
        <div class="food-icons burger">🍔</div>
        <div class="food-icons pizza">🍕</div>
        <div class="food-icons fries">🍟</div>
        <div class="food-icons drink">🥤</div>
        <div class="food-icons sushi">🍣</div>
        <div class="food-icons icecream">🍦</div>
        <div class="food-icons coffee">☕</div>
        <div class="food-icons cake">🍰</div>

        <!-- Contenu principal -->
        <div class="container">
            <div class="logo-container">
                <!-- Remplacement du texte "TOUCH EAT" par le logo -->
                <img src="{{ asset('logo-touch-eat.png') }}" alt="Touch Eat Logo" class="logo-img" onerror="this.onerror=null; this.style.display='none'; document.getElementById('fallback-logo').style.display='block';">
                <!-- Fallback en cas d'image non trouvée -->
                <h1 id="fallback-logo" style="display: none; font-family: 'Poppins', sans-serif; font-size: 4rem; color: white; text-shadow: 4px 4px 0px rgba(0, 0, 0, 0.2);">
                    TOUCH EAT
                </h1>
            </div>
            <p class="subtitle">Votre expérience culinaire digitale</p>
            <button class="start-button" onclick="startExperience()">
                Commencer
            </button>

            <!-- Choix du type d'utilisateur pour les non connectés -->
            @guest
            <div class="choice-buttons">
                <button class="choice-button" onclick="window.location.href='{{ route('client.auth') }}'">
                    🍽️ Je suis Client
                </button>
                <button class="choice-button" onclick="window.location.href='{{ route('admin.auth') }}'">
                    👨‍💼 Je suis Admin
                </button>
            </div>
            @endguest
        </div>

        <script>
            function startExperience() {
                // Animation de clic
                const button = document.querySelector('.start-button');
                button.style.transform = 'scale(0.95)';
                
                setTimeout(() => {
                    button.style.transform = 'scale(1)';
                    
                    // Redirection après l'animation
                    setTimeout(() => {
                        @auth
                            @if(Auth::user()->role === 'admin')
                                window.location.href = "{{ route('admin.dashboard') }}";
                            @else
                                window.location.href = "{{ url('/home') }}";
                            @endif
                        @else
                            // Pour les non connectés, montrer les choix
                            const choiceButtons = document.querySelector('.choice-buttons');
                            choiceButtons.style.display = 'flex';
                            choiceButtons.style.animation = 'fadeIn 0.5s ease-in';
                        @endauth
                    }, 200);
                }, 150);
            }

            // Effet de parallaxe sur les icônes de nourriture
            document.addEventListener('mousemove', (e) => {
                const icons = document.querySelectorAll('.food-icons');
                const mouseX = e.clientX / window.innerWidth;
                const mouseY = e.clientY / window.innerHeight;
                
                icons.forEach((icon, index) => {
                    const speed = (index + 1) * 0.3; // Réduit la vitesse pour un effet plus subtil
                    const x = (mouseX - 0.5) * speed * 15;
                    const y = (mouseY - 0.5) * speed * 15;
                    
                    icon.style.transform = `translate(${x}px, ${y}px)`;
                });
            });

            // Animation d'apparition pour les boutons de choix
            const style = document.createElement('style');
            style.textContent = `
                @keyframes fadeIn {
                    from { opacity: 0; transform: translateY(20px); }
                    to { opacity: 1; transform: translateY(0); }
                }
            `;
            document.head.appendChild(style);

            // Afficher les boutons de choix au clic sur "Commencer" pour les non connectés
            document.addEventListener('DOMContentLoaded', function() {
                @guest
                document.querySelector('.choice-buttons').style.display = 'none';
                @endguest
                
                // Vérifier si l'image du logo existe
                const logoImg = document.querySelector('.logo-img');
                logoImg.addEventListener('error', function() {
                    this.style.display = 'none';
                    document.getElementById('fallback-logo').style.display = 'block';
                });
            });
        </script>
    </body>
</html>
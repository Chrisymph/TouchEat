<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Dashboard - Restaurant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Chart.js chargé en premier -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="{{ asset('bootstrap/icons/bootstrap-icons.css') }}">
</head>
<style>
   body {
        background: linear-gradient(180deg, #fbefe9 0%, #f9eae4 100%);
        font-family: 'Poppins', sans-serif;
        color: #2b2b2b;
    }
</style>
<body class="bg-gray-100 min-h-screen">
    <header class="py-4 px-6 flex flex-col items-center text-gray-800">
        <div class="w-full flex justify-between items-center mb-2">
            <form action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button type="submit" class="ml-10 bg-gray-100 tbg-red-500 text-black px-4 py-2 rounded hover:bg-red-600">
                    ← Déconnexion
                </button>
            </form>

            <h1 class="text-2xl font-bold text-center flex-1">Dashboard Administrateur</h1>

            <span class="mr-12 text-sm text-gray-600">{{ \Carbon\Carbon::now()->translatedFormat('d/m/Y') }}</span>
        </div>

        <div class="text-center text-gray-600 text-sm">
            Responsable : <span class="font-semibold">{{ Auth::user()->manager_name ?? 'Admin' }}</span>
        </div>
    </header>

    <!-- Contenu principal -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6" id="success-notification">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <script>
        // Fonctions utilitaires pour les interactions
        function showToast(message, type = 'success', duration = 4000) {
            const existingToasts = document.querySelectorAll('.custom-toast');
            existingToasts.forEach(toast => toast.remove());

            const toast = document.createElement('div');
            toast.className = `custom-toast fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white font-semibold z-50 ${
                type === 'success' ? 'bg-green-500' : 'bg-red-500'
            }`;
            toast.textContent = message;
            document.body.appendChild(toast);

            setTimeout(() => {
                if (toast.parentNode) {
                    toast.style.transition = 'opacity 0.5s ease';
                    toast.style.opacity = '0';
                    setTimeout(() => {
                        if (toast.parentNode) {
                            toast.parentNode.removeChild(toast);
                        }
                    }, 500);
                }
            }, duration);
        }

        function confirmAction(message) {
            return confirm(message);
        }

        // Masquer automatiquement les notifications de succès après 10 secondes
        document.addEventListener('DOMContentLoaded', function() {
            // Notification de succès Laravel
            const successNotification = document.getElementById('success-notification');
            if (successNotification) {
                setTimeout(() => {
                    if (successNotification.parentNode) {
                        successNotification.style.transition = 'opacity 0.5s ease';
                        successNotification.style.opacity = '0';
                        setTimeout(() => {
                            if (successNotification.parentNode) {
                                successNotification.parentNode.removeChild(successNotification);
                            }
                        }, 500);
                    }
                }, 10000); // 10 secondes
            }

            // Gestion spécifique pour les notifications Alpine.js
            const flashMessages = document.querySelectorAll('[x-data="{ show: true }"]');
            flashMessages.forEach(message => {
                setTimeout(() => {
                    if (message && message.parentNode) {
                        message.style.transition = 'opacity 0.5s ease';
                        message.style.opacity = '0';
                        setTimeout(() => {
                            if (message.parentNode) {
                                message.parentNode.removeChild(message);
                            }
                        }, 500);
                    }
                }, 10000);
            });

            // Vérifier que Chart.js est bien chargé
            if (typeof Chart === 'undefined') {
                console.error('Chart.js non chargé correctement');
            } else {
                console.log('Chart.js prêt');
            }
        });
    </script>

    <style>
        .custom-toast {
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
</body>
</html>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Dashboard - Restaurant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="{{ asset('bootstrap/icons/bootstrap-icons.css') }}">
    <script src="{{ asset('js/chart.umd.min.js') }}"></script>
</head>
<style>
    body {
        background: linear-gradient(180deg, #fbefe9 0%, #f9eae4 100%);
        font-family: 'Poppins', sans-serif;
        color: #2b2b2b;
    }
</style>
<body class="bg-gray-100 min-h-screen">
    <!-- Header -->
    <header class="py-4 px-6 flex flex-col items-center text-gray-800">
  <div class="w-full flex justify-between items-center mb-2">
    <!-- Bouton gauche -->
    <form action="{{ route('admin.logout') }}" method="POST">
      @csrf
      <button type="submit" class="ml-10 bg-gray-100 tbg-red-500 text-black px-4 py-2 rounded hover:bg-red-600">
        ← Déconnexion
      </button>
    </form>

    <!-- Titre centré -->
    <h1 class="text-2xl font-bold text-center flex-1">Dashboard Administrateur</h1>

    <!-- Date à droite -->
    <span class="mr-12 text-sm text-gray-600">{{ \Carbon\Carbon::now()->translatedFormat('d/m/Y') }}</span>
  </div>

  <!-- Ligne du responsable -->
  <div class="text-center text-gray-600 text-sm">
    Responsable : <span class="font-semibold">{{ Auth::user()->manager_name ?? 'Admin' }}</span>
  </div>
</header>

    <!-- Contenu principal -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
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
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 p-4 rounded-md text-white z-50 ${
                type === 'success' ? 'bg-green-500' : 'bg-red-500'
            }`;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => toast.remove(), 3000);
        }

        function confirmAction(message) {
            return confirm(message);
        }

        // 🎚️ Gestion de l’interrupteur "Disponible" pour les articles du menu
        // Change la couleur et la position du bouton (rouge ↔ orange)
        function toggleSwitch(id) {
        const checkbox = document.getElementById(`switch-${id}`);
        const knob = document.getElementById(`knob-${id}`);
        const track = knob.parentElement;

        checkbox.checked = !checkbox.checked; // change l’état

        if (checkbox.checked) {
            track.style.backgroundColor = '#f97316'; // orange
            knob.style.transform = 'translateX(20px)';
        } else {
            track.style.backgroundColor = '#ef4444'; // rouge
            knob.style.transform = 'translateX(0)';
        }
        }
    </script>

</body>
</html>
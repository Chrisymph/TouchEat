<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interface Client - Table {{ $tableNumber }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- En-tête -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Bienvenue à la Table {{ $tableNumber }}</h1>
                    <p class="text-gray-600">Interface de commande client</p>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">Table {{ $tableNumber }}</span>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" 
                                class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600">
                            Déconnexion
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Contenu principal -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Carte Menu -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Notre Menu</h2>
                <p class="text-gray-600">Parcourez notre sélection de plats...</p>
                <!-- Ici vous ajouterez la liste des plats -->
            </div>

            <!-- Carte Panier -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Votre Panier</h2>
                <p class="text-gray-600">Vos articles sélectionnés...</p>
                <!-- Ici vous ajouterez le panier -->
            </div>

            <!-- Carte Commandes -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Vos Commandes</h2>
                <p class="text-gray-600">Historique des commandes...</p>
                <!-- Ici vous ajouterez l'historique -->
            </div>
        </div>
    </div>
</body>
</html>
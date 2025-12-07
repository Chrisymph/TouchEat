<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Client</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-green-100 to-blue-100 p-4">
    <!-- Bouton Accueil en haut à gauche -->
    <div class="absolute top-4 left-4 z-10">
        <a href="{{ route('welcome') }}" 
           class="inline-flex items-center bg-white text-gray-700 hover:text-green-600 hover:bg-green-50 rounded-lg px-4 py-2 text-sm font-medium border border-gray-300 shadow-sm transition-all duration-200">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
            </svg>
            Accueil
        </a>
    </div>

    <div x-data="{ 
        isSignUp: false, 
        loading: false, 
        tableNumber: '', 
        password: '',
        errors: {}
    }" class="min-h-screen flex items-center justify-center">
        
        <!-- Card principale -->
        <div class="w-full max-w-md bg-white rounded-lg border border-gray-200 shadow-sm">
            <div class="p-6 text-center">
                <h2 class="text-2xl font-bold mb-2" 
                    x-text="isSignUp ? 'Créer un compte client' : 'Connexion Client'">
                </h2>
                <p class="text-gray-600 mb-6">Accédez à l'interface de commande</p>
                
                <!-- Messages d'alerte -->
                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        {{ session('success') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Formulaire -->
                <form method="POST" 
                      x-bind:action="isSignUp ? '{{ route('client.register') }}' : '{{ route('client.login') }}'"
                      @submit="
                        loading = true;
                        errors = {};
                        if (!tableNumber || !password) {
                            errors.general = 'Veuillez remplir tous les champs';
                            loading = false;
                            $event.preventDefault();
                        } else if (isNaN(parseInt(tableNumber)) || parseInt(tableNumber) <= 0) {
                            errors.tableNumber = 'Numéro de table invalide';
                            loading = false;
                            $event.preventDefault();
                        }
                      ">
                    @csrf
                    
                    <div class="space-y-4">
                        <!-- Champ Numéro de table -->
                        <div>
                            <label for="tableNumber" class="block text-sm font-medium text-gray-700 text-left mb-1">
                                Numéro de table
                            </label>
                            <input type="number" 
                                   id="tableNumber" 
                                   name="tableNumber"
                                   x-model="tableNumber"
                                   placeholder="Ex: 5"
                                   min="1"
                                   class="w-full h-10 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                   required>
                            <template x-if="errors.tableNumber">
                                <p class="text-red-500 text-xs mt-1 text-left" x-text="errors.tableNumber"></p>
                            </template>
                        </div>
                        
                        <!-- Champ Mot de passe -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 text-left mb-1">
                                Mot de passe
                            </label>
                            <input type="password" 
                                   id="password" 
                                   name="password"
                                   x-model="password"
                                   placeholder="Entrez votre mot de passe"
                                   class="w-full h-10 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                   required>
                        </div>

                        <!-- Message d'erreur général -->
                        <template x-if="errors.general">
                            <p class="text-red-500 text-sm" x-text="errors.general"></p>
                        </template>

                        <!-- Bouton de soumission -->
                        <button type="submit" 
                                x-bind:disabled="loading"
                                class="w-full h-10 bg-green-600 text-white rounded-md text-sm font-medium hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                x-text="loading ? 'Connexion...' : (isSignUp ? 'Créer le compte' : 'Se connecter')">
                        </button>
                    </div>
                </form>

                <!-- Lien pour basculer entre connexion/inscription -->
                <div class="mt-4 text-center">
                    <button type="button" 
                            @click="isSignUp = !isSignUp; tableNumber = ''; password = ''; errors = {}"
                            class="text-green-600 text-sm hover:underline">
                        <span x-text="isSignUp ? 'Déjà un compte ? Se connecter' : 'Pas de compte ? Créer un compte'"></span>
                    </button>
                </div>

                <!-- Lien vers l'accès administrateur -->
                <div class="mt-6 text-center">
                    <a href="{{ route('admin.auth') }}" 
                       class="inline-block border border-gray-300 text-gray-700 rounded-md px-4 py-2 text-sm hover:bg-gray-50">
                        Accès administrateur
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Gestion des messages toast (simulation de sonner)
        document.addEventListener('DOMContentLoaded', function() {
            @if(session('success'))
                showToast('{{ session('success') }}', 'success');
            @endif
            
            @if($errors->any())
                @foreach($errors->all() as $error)
                    showToast('{{ $error }}', 'error');
                @endforeach
            @endif
        });

        function showToast(message, type = 'success') {
            // Créer un élément toast simple
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 p-4 rounded-md text-white ${
                type === 'success' ? 'bg-green-500' : 'bg-red-500'
            } z-50`;
            toast.textContent = message;
            
            document.body.appendChild(toast);
            
            // Supprimer après 5 secondes
            setTimeout(() => {
                toast.remove();
            }, 5000);
        }
    </script>
</body>
</html>
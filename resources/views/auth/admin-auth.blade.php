<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Inclure Alpine.js pour l'interactivité -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-100 to-purple-100 p-4">
    <!-- Bouton Accueil en haut à gauche -->
    <div class="absolute top-4 left-4 z-10">
        <a href="{{ route('welcome') }}" 
           class="inline-flex items-center bg-white text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg px-4 py-2 text-sm font-medium border border-gray-300 shadow-sm transition-all duration-200">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
            </svg>
            Accueil
        </a>
    </div>

    <div x-data="{ isSignUp: false, loading: false, managerName: '', password: '' }" 
         class="min-h-screen flex items-center justify-center">
        
        <!-- Card principale -->
        <div class="w-full max-w-md bg-white rounded-lg border border-gray-200 shadow-sm">
            <div class="p-6 text-center">
                <h2 class="text-2xl font-bold mb-2" x-text="isSignUp ? 'Créer un compte admin' : 'Connexion Admin'"></h2>
                <p class="text-gray-600 mb-6">Accédez à l'interface d'administration</p>
                
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
                <form method="POST" x-bind:action="isSignUp ? '{{ route('admin.register') }}' : '{{ route('admin.login') }}'"
                      @submit="loading = true">
                    @csrf
                    
                    <div class="space-y-4">
                        <!-- Champ Nom du responsable -->
                        <div>
                            <label for="managerName" class="block text-sm font-medium text-gray-700 text-left mb-1">
                                Nom du responsable
                            </label>
                            <input type="text" 
                                   id="managerName" 
                                   name="managerName"
                                   x-model="managerName"
                                   placeholder="Ex: Jean Dupont"
                                   class="w-full h-10 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   required>
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
                                   class="w-full h-10 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   required>
                        </div>

                        <!-- Bouton de soumission -->
                        <button type="submit" 
                                x-bind:disabled="loading"
                                class="w-full h-10 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                x-text="loading ? 'Connexion...' : (isSignUp ? 'Créer le compte' : 'Se connecter')">
                        </button>
                    </div>
                </form>

                <!-- Lien pour basculer entre connexion/inscription -->
                <div class="mt-4 text-center">
                    <button type="button" 
                            @click="isSignUp = !isSignUp; managerName = ''; password = ''"
                            class="text-blue-600 text-sm hover:underline">
                        <span x-text="isSignUp ? 'Déjà un compte ? Se connecter' : 'Pas de compte ? Créer un compte'"></span>
                    </button>
                </div>

                <!-- Lien vers l'accès client -->
                <div class="mt-6 text-center">
                    <a href="{{ route('client.auth') }}" 
                       class="inline-block border border-gray-300 text-gray-700 rounded-md px-4 py-2 text-sm hover:bg-gray-50">
                        Accès client
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
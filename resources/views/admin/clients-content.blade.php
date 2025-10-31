<div class="clients-content">
    <!-- En-tête avec bouton d'ajout -->
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Gestion des Clients</h2>
        <button onclick="openAddClientModal()" 
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition-all duration-300 shadow-md hover:shadow-lg">
            <span class="flex items-center space-x-2">
                <span>➕</span>
                <span>Ajouter Client</span>
            </span>
        </button>
    </div>

    <!-- Liste des clients liés -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <!-- En-tête du tableau -->
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
            <div class="grid grid-cols-12 gap-4 text-sm font-semibold text-gray-700">
                <div class="col-span-4">Client</div>
                <div class="col-span-3">Table</div>
                <div class="col-span-3">Statut</div>
                <div class="col-span-2 text-center">Actions</div>
            </div>
        </div>

        <!-- Corps du tableau -->
        <div class="divide-y divide-gray-200">
            @if($linkedClients->count() === 0)
                <div class="px-6 py-12 text-center">
                    <div class="text-6xl mb-4 text-gray-300">👥</div>
                    <h3 class="text-xl font-semibold text-gray-500 mb-2">Aucun client lié</h3>
                    <p class="text-gray-400 mb-6">Commencez par ajouter des clients à votre compte</p>
                    <button onclick="openAddClientModal()" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition-all duration-300">
                        Ajouter votre premier client
                    </button>
                </div>
            @else
                @foreach($linkedClients as $client)
                <div class="px-6 py-4 hover:bg-gray-50 transition-colors duration-200">
                    <div class="grid grid-cols-12 gap-4 items-center">
                        <!-- Informations client -->
                        <div class="col-span-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                    <span class="text-blue-600 font-semibold">{{ substr($client->name, 0, 2) }}</span>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-800">{{ $client->name }}</h4>
                                    <p class="text-sm text-gray-500">{{ $client->email }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Numéro de table -->
                        <div class="col-span-3">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                Table #{{ $client->table_number }}
                            </span>
                        </div>

                        <!-- Statut -->
                        <div class="col-span-3">
                            @if($client->is_suspended)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                    <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                                    Suspendu
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                    <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                                    Actif
                                </span>
                            @endif
                        </div>

                        <!-- Actions -->
                        <div class="col-span-2">
                            <div class="flex justify-center space-x-2">
                                <!-- Bouton Suspendre/Activer -->
                                @if(!$client->is_suspended)
                                    <button onclick="suspendClient({{ $client->id }})" 
                                            class="suspend-client-btn bg-yellow-500 hover:bg-yellow-600 text-white p-2 rounded-lg transition-colors duration-200 tooltip"
                                            title="Suspendre le client"
                                            data-client-id="{{ $client->id }}">
                                        ⏸️
                                    </button>
                                @else
                                    <button onclick="activateClient({{ $client->id }})" 
                                            class="activate-client-btn bg-green-500 hover:bg-green-600 text-white p-2 rounded-lg transition-colors duration-200 tooltip"
                                            title="Activer le client"
                                            data-client-id="{{ $client->id }}">
                                        ▶️
                                    </button>
                                @endif

                                <!-- Bouton Retirer -->
                                <button onclick="unlinkClient({{ $client->id }})" 
                                        class="unlink-client-btn bg-red-500 hover:bg-red-600 text-white p-2 rounded-lg transition-colors duration-200 tooltip"
                                        title="Retirer le client"
                                        data-client-id="{{ $client->id }}">
                                    ❌
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            @endif
        </div>
    </div>
</div>

<script>
// Fonction pour suspendre un client
function suspendClient(clientId) {
    if (confirm('Êtes-vous sûr de vouloir suspendre ce client ?')) {
        fetch(`/admin/clients/${clientId}/suspend`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                // CORRECTION : Rafraîchir le contenu des clients
                setTimeout(() => {
                    if (window.dashboardComponent) {
                        window.dashboardComponent.loadClients();
                    } else {
                        location.reload();
                    }
                }, 1000);
            } else {
                showNotification('Erreur: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showNotification('Erreur lors de la suspension du client', 'error');
        });
    }
}

// Fonction pour activer un client
function activateClient(clientId) {
    if (confirm('Êtes-vous sûr de vouloir activer ce client ?')) {
        fetch(`/admin/clients/${clientId}/activate`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                // CORRECTION : Rafraîchir le contenu des clients
                setTimeout(() => {
                    if (window.dashboardComponent) {
                        window.dashboardComponent.loadClients();
                    } else {
                        location.reload();
                    }
                }, 1000);
            } else {
                showNotification('Erreur: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showNotification('Erreur lors de l\'activation du client', 'error');
        });
    }
}

// Fonction pour retirer un client
function unlinkClient(clientId) {
    if (confirm('Êtes-vous sûr de vouloir retirer ce client ?')) {
        fetch(`/admin/clients/${clientId}/unlink`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                // CORRECTION : Rafraîchir le contenu des clients
                setTimeout(() => {
                    if (window.dashboardComponent) {
                        window.dashboardComponent.loadClients();
                    } else {
                        location.reload();
                    }
                }, 1000);
            } else {
                showNotification('Erreur: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showNotification('Erreur lors du retrait du client', 'error');
        });
    }
}

// Fonction pour afficher les notifications
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white font-semibold z-50 ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 'bg-blue-500'
    }`;
    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 3000);
}
</script>
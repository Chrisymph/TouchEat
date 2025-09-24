@extends('layouts.admin')

@section('content')
<div x-data="{ activeTab: 'overview' }">
    <!-- Onglets -->
    <div class="mb-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <button @click="activeTab = 'overview'" 
                        :class="activeTab === 'overview' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                    Vue d'ensemble
                </button>
                <button @click="activeTab = 'orders'" 
                        :class="activeTab === 'orders' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                    Commandes
                </button>
                <button @click="activeTab = 'menu'" 
                        :class="activeTab === 'menu' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                    Menu
                </button>
                <button @click="activeTab = 'reports'" 
                        :class="activeTab === 'reports' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                    Rapports
                </button>
            </nav>
        </div>
    </div>

    <!-- Vue d'ensemble -->
    <div x-show="activeTab === 'overview'" class="space-y-6">
        <!-- Cartes de statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600 text-2xl">📊</div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Commandes Aujourd'hui</p>
                        <p class="text-2xl font-bold text-primary">{{ $stats['todayOrders'] }}</p>
                        <p class="text-xs text-gray-500">+2 depuis hier</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 text-2xl">⏳</div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Commandes en Attente</p>
                        <p class="text-2xl font-bold text-yellow-600">{{ $stats['pendingOrders'] }}</p>
                        <p class="text-xs text-gray-500">À traiter</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600 text-2xl">💰</div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Revenus Aujourd'hui</p>
                        <p class="text-2xl font-bold text-green-600">{{ number_format($stats['todayRevenue'], 0, ',', ' ') }} FCFA</p>
                        <p class="text-xs text-gray-500">+12% depuis hier</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600 text-2xl">🪑</div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Tables Actives</p>
                        <p class="text-2xl font-bold text-yellow-600">{{ $stats['activeTables'] }}/12</p>
                        <p class="text-xs text-gray-500">Tables occupées</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Commandes récentes -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-lg font-semibold">Commandes Récentes</h2>
                    <a href="{{ route('admin.orders') }}" class="text-blue-600 hover:text-blue-800 text-sm">
                        Voir tout →
                    </a>
                </div>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @foreach($recentOrders as $order)
                    <div class="flex items-center justify-between p-4 border rounded-lg">
                        <div>
                            <p class="font-semibold">#{{ $order->id }}</p>
                            <p class="text-sm text-gray-600">
                                Table {{ $order->table_number }} • {{ $order->items->count() }} articles
                            </p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="text-right">
                                <p class="font-semibold">{{ number_format($order->total, 0, ',', ' ') }} FCFA</p>
                                <p class="text-sm text-gray-600">
                                    {{ $order->created_at->format('H:i') }}
                                </p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold 
                                @if($order->status === 'commandé') bg-yellow-100 text-yellow-800
                                @elseif($order->status === 'en_cours') bg-blue-100 text-blue-800
                                @elseif($order->status === 'prêt') bg-green-100 text-green-800
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ ucfirst($order->status) }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Actions rapides -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <a href="{{ route('admin.orders') }}" 
               class="bg-orange-600 text-white rounded-lg p-6 text-center hover:bg-blue-700 transition-colors">
                <div class="space-y-2">
                    <div class="text-3xl">📋</div>
                    <div class="text-lg font-semibold">Gérer les Commandes</div>
                </div>
            </a>

            <a href="{{ route('admin.menu') }}" 
               class="bg-red-600 text-white rounded-lg p-6 text-center hover:bg-gray-700 transition-colors">
                <div class="space-y-2">
                    <div class="text-3xl">🍽️</div>
                    <div class="text-lg font-semibold">Gérer le Menu</div>
                </div>
            </a>

            <button class="bg-white border border-gray-300 rounded-lg p-6 text-center hover:bg-gray-50 transition-colors">
                <div class="space-y-2">
                    <div class="text-3xl">🖨️</div>
                    <div class="text-lg font-semibold text-gray-900">Imprimer Reçus</div>
                </div>
            </button>
        </div>
    </div>

    <!-- Autres onglets seront chargés via des pages séparées -->
    <div x-show="activeTab !== 'overview'">
        <div class="text-center py-12">
            <div class="text-6xl mb-4">🔄</div>
            <p class="text-lg text-gray-600">Chargement du contenu...</p>
            <p class="text-sm text-gray-500 mt-2">
                <a :href="activeTab === 'orders' ? '{{ route('admin.orders') }}' : 
                         activeTab === 'menu' ? '{{ route('admin.menu') }}' : 
                         '{{ route('admin.reports') }}'" 
                   class="text-blue-600 hover:text-blue-800">
                    Cliquez ici pour accéder directement à la page
                </a>
            </p>
        </div>
    </div>
</div>
@endsection
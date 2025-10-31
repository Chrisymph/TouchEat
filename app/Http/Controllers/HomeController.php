<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index()
    {
        // Vérifier que l'utilisateur est connecté
        if (!Auth::check()) {
            return redirect()->route('client.auth');
        }

        // Rediriger selon le rôle
        if (Auth::user()->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        // Pour les clients, utiliser la route existante qui a déjà toute la logique
        return redirect()->route('client.dashboard');
    }
}
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    public function dashboard()
    {
        // Vérifier que l'utilisateur est connecté et est un client
        if (!Auth::check() || Auth::user()->role !== 'client') {
            return redirect()->route('client.auth');
        }

        // Afficher l'interface client
        return view('client.dashboard', [
            'tableNumber' => Auth::user()->table_number
        ]);
    }
}
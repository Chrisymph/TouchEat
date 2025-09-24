<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index()
    {
        // Vérifier que l'utilisateur est un client
        if (!Auth::check() || !Auth::user()->isClient()) {
            return redirect()->route('client.auth');
        }

        return view('client.dashboard', [
            'user' => Auth::user(),
            'tableNumber' => Auth::user()->table_number
        ]);
    }
}
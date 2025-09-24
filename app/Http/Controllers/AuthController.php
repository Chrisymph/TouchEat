<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class AuthController extends Controller
{

    // Afficher le formulaire d'authentification client
    public function showClientAuth()
    {
        // Rediriger si déjà authentifié comme client
        if (Auth::check() && Auth::user()->role === 'client') {
            return redirect()->route('home');
        }
        
        // Rediriger les admins vers l'interface admin
        if (Auth::check() && Auth::user()->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        return view('auth.client-auth');
    }

    // Connexion client
    public function clientLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tableNumber' => 'required|integer|min:1',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $email = 'table' . $request->tableNumber . '@restaurant.com';
        
        $credentials = [
            'email' => $email,
            'password' => $request->password,
            'role' => 'client'
        ];

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->route('home')->with('success', 'Connexion réussie!');
        }

        return back()->withErrors([
            'tableNumber' => 'Numéro de table ou mot de passe incorrect',
        ])->withInput();
    }

    // Inscription client
    public function clientRegister(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tableNumber' => 'required|integer|min:1',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $email = 'table' . $request->tableNumber . '@restaurant.com';

        // Vérifier si la table existe déjà
        if (User::where('email', $email)->exists()) {
            return back()->withErrors([
                'tableNumber' => 'Cette table est déjà enregistrée',
            ])->withInput();
        }

        // Vérifier si le numéro de table est déjà utilisé
        if (User::where('table_number', $request->tableNumber)->exists()) {
            return back()->withErrors([
                'tableNumber' => 'Ce numéro de table est déjà attribué',
            ])->withInput();
        }

        // Créer l'utilisateur client
        $user = User::create([
            'name' => 'Table ' . $request->tableNumber,
            'email' => $email,
            'password' => Hash::make($request->password),
            'role' => 'client',
            'table_number' => $request->tableNumber,
        ]);

        Auth::login($user);

        return redirect()->route('home')->with('success', 'Compte créé avec succès!');
    }


    // Afficher le formulaire d'authentification admin
    public function showAdminAuth()
    {
        // Rediriger si déjà authentifié comme admin
        if (Auth::check() && Auth::user()->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }
        
        // Rediriger les clients vers l'interface client
        if (Auth::check() && Auth::user()->role === 'client') {
            return redirect()->route('home');
        }

        return view('auth.admin-auth');
    }

    // Connexion admin
    public function adminLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'managerName' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $email = strtolower(str_replace(' ', '', $request->managerName)) . '@admin.restaurant.com';
        
        $credentials = [
            'email' => $email,
            'password' => $request->password,
            'role' => 'admin'
        ];

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->route('admin.dashboard')->with('success', 'Connexion réussie!');
        }

        return back()->withErrors([
            'managerName' => 'Nom de responsable ou mot de passe incorrect',
        ])->withInput();
    }

    // Inscription admin
    public function adminRegister(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'managerName' => 'required|string|unique:users,manager_name',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $email = strtolower(str_replace(' ', '', $request->managerName)) . '@admin.restaurant.com';

        // Vérifier si l'utilisateur existe déjà
        if (User::where('email', $email)->exists()) {
            return back()->withErrors([
                'managerName' => 'Ce responsable est déjà enregistré',
            ])->withInput();
        }

        // Créer l'utilisateur
        $user = User::create([
            'name' => $request->managerName,
            'email' => $email,
            'password' => Hash::make($request->password),
            'role' => 'admin',
            'manager_name' => $request->managerName,
        ]);

        Auth::login($user);

        return redirect()->route('admin.dashboard')->with('success', 'Compte créé avec succès!');
    }

    // Déconnexion
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}

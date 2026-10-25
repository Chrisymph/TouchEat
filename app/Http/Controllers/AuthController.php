<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class AuthController extends Controller
{

public function loginViaQr(string $token)
{
    $user = User::where('qr_token', $token)
                ->where('role', 'client')
                ->first();

    if (!$user) {
        return redirect()->route('welcome')
            ->with('error', 'QR code invalide ou expiré.');
    }

    // Déconnecte un éventuel utilisateur précédent (admin ou autre table)
    if (Auth::check() && Auth::user()->id !== $user->id) {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }

    Auth::login($user);
    request()->session()->regenerate();

    return redirect()->route('client.dashboard')
        ->with('success', 'Bienvenue à la table ' . $user->table_number);
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

        return redirect()->route('admin.dashboard')->with('success', 'Connexion réussie!');
    }

    // Déconnexion
public function logout(Request $request)
{
    // ⚡ IMPORTANT : capturer le rôle AVANT de déconnecter
    $role = Auth::check() ? Auth::user()->role : null;

    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    // 🎯 Client → page de remerciements
    if ($role === 'client') {
        return redirect()->route('thank.you');
    }

    // Admin ou autre → accueil
    return redirect('/');
}
}

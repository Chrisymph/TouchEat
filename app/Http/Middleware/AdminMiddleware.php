<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('admin.auth')->with('error', 'Veuillez vous connecter en tant qu\'administrateur.');
        }

        if (Auth::user()->role !== 'admin') {
            return redirect()->route('client.auth')->with('error', 'Accès réservé aux administrateurs.');
        }

        return $next($request);
    }
}
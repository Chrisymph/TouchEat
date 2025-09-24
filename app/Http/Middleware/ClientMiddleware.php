<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check() || !Auth::user()->isClient()) {
            return redirect('/client-auth')->with('error', 'Veuillez vous connecter en tant que client');
        }

        return $next($request);
    }
}
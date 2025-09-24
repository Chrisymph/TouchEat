<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo(Request $request): ?string
    {
        // Rediriger vers l'authentification admin si la route commence par /admin
        if ($request->is('admin/*')) {
            return route('admin.auth');
        }
        
        // Rediriger vers l'authentification client pour les autres routes
        return route('client.auth');
    }
}

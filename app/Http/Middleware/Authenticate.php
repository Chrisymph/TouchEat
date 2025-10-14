<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request): ?string
    {
        if (!$request->expectsJson()) {
            // Rediriger vers l'authentification admin si la route commence par /admin
            if ($request->is('admin/*')) {
                return route('admin.auth');
            }
            
            // Rediriger vers l'authentification client pour les autres routes
            return route('client.auth');
        }
        
        return null;
    }
}
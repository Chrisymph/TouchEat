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
        if ($request->is('admin/*')) {
            return route('admin.auth');
        }
        // Client non authentifié → retour accueil (scan QR)
        return route('welcome');
    }
    return null;
}
}
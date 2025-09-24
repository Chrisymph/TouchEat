<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'manager_name',
        'table_number',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'table_number' => 'integer',
    ];

    // Vérifier si l'utilisateur est un admin
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    // Vérifier si l'utilisateur est un client
    public function isClient()
    {
        return $this->role === 'client';
    }
}
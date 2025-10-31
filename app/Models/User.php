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
        'is_suspended',
        'suspended_until',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'table_number' => 'integer',
        'is_suspended' => 'boolean',
        'suspended_until' => 'datetime',
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

    // Vérifier si le compte est suspendu - CORRECTION
    public function isSuspended()
    {
        // CORRECTION : Vérifier d'abord si suspended_until est défini et dans le futur
        if ($this->suspended_until && $this->suspended_until->isFuture()) {
            return true;
        }
        
        // Ensuite vérifier is_suspended
        return $this->is_suspended;
    }

    // Relation pour les clients liés à un admin
    public function linkedClients()
    {
        return $this->belongsToMany(User::class, 'admin_client', 'admin_id', 'client_id')
                    ->withPivot('created_at')
                    ->withTimestamps();
    }

    // Relation pour les admins qui ont lié ce client
    public function linkedAdmins()
    {
        return $this->belongsToMany(User::class, 'admin_client', 'client_id', 'admin_id')
                    ->withPivot('created_at')
                    ->withTimestamps();
    }

    // Suspendre le compte
    public function suspend()
    {
        $this->update([
            'is_suspended' => true,
            'suspended_until' => null, // Suspension indéfinie
        ]);
    }

    // Activer le compte
    public function activate()
    {
        $this->update([
            'is_suspended' => false,
            'suspended_until' => null,
        ]);
    }
}
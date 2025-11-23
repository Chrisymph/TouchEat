<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'table_number',
        'customer_name',
        'customer_phone',
        'order_type', // sur_place, emporter, livraison
        'status', // commandé, en_cours, prêt, terminé, livré
        'estimated_time',
        'total',
        'delivery_address',
        'delivery_notes',
        'marked_ready_at',
        'payment_status', // 🔥 ASSUREZ-VOUS QUE C'EST BIEN PRÉSENT
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'marked_ready_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Vérifie si le client (table ou téléphone) a au moins une commande précédente terminée/livrée.
     */
    public function hasPreviousOrders(): bool
    {
        $query = self::where('id', '<>', $this->id)
            ->whereIn('status', ['terminé', 'livré']);

        // Si commande sur place : on compare par table_number (si défini)
        if (!empty($this->table_number)) {
            $query->where('table_number', $this->table_number);
        }
        // Sinon on essaie par téléphone client (livraison/emporter)
        elseif (!empty($this->customer_phone)) {
            $query->where('customer_phone', $this->customer_phone);
        } else {
            // fallback : si ni table ni téléphone, on ne considère pas comme ayant précédentes commandes
            return false;
        }

        return $query->exists();
    }

    /**
     * Détecte s'il y a eu de réels ajouts d'articles pendant la commande en cours.
     */
    public function hasRecentAdditions(int $secondsThreshold = 30): bool
    {
        $orderCreated = $this->created_at ? $this->created_at : Carbon::now();

        foreach ($this->items as $item) {
            // s'il manque les timestamps côté DB, on ignore cet item
            if (!$item->created_at) {
                continue;
            }

            // Si l'item a été créé bien après la création de la commande -> ajout
            $diffCreated = $item->created_at->diffInSeconds($orderCreated);
            if ($diffCreated > $secondsThreshold) {
                return true;
            }

            // Si l'item a été mis à jour après la création de la commande -> ajout ou modification
            if ($item->updated_at && $item->updated_at->greaterThan($orderCreated)) {
                return true;
            }
        }

        // aucun item n'a de création/modif significative après la commande
        return false;
    }

    /**
     * Scope pour les commandes payées
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'payé');
    }

    /**
     * Scope pour les commandes en attente de paiement
     */
    public function scopePendingPayment($query)
    {
        return $query->where('payment_status', 'en_attente');
    }
}
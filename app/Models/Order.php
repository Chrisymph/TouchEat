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
        'started_at',
        'total',
        'delivery_address',
        'delivery_notes',
        'marked_ready_at',
        'payment_status', // 🔥 ASSUREZ-VOUS QUE C'EST BIEN PRÉSENT
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'marked_ready_at' => 'datetime',
        'started_at' => 'datetime',
        'estimated_time' => 'integer',
        'total' => 'decimal:2',
    ];

    /**
     * Accesseur pour started_at - CORRECTION CRITIQUE
     */
    public function getStartedAtAttribute($value)
    {
        if (!$value) {
            return null;
        }
        
        // Si c'est déjà une instance Carbon, retournez-la
        if ($value instanceof Carbon) {
            return $value;
        }
        
        // Sinon, parsez la chaîne
        try {
            return Carbon::parse($value);
        } catch (\Exception $e) {
            // Si le parsing échoue, retournez null
            return null;
        }
    }

    /**
     * Mutateur pour started_at - CORRECTION CRITIQUE
     */
    public function setStartedAtAttribute($value)
    {
        if (!$value) {
            $this->attributes['started_at'] = null;
        } elseif ($value instanceof Carbon) {
            $this->attributes['started_at'] = $value;
        } elseif (is_string($value)) {
            try {
                $this->attributes['started_at'] = Carbon::parse($value);
            } catch (\Exception $e) {
                // Si le parsing échoue, utilisez maintenant
                $this->attributes['started_at'] = Carbon::now();
            }
        } else {
            $this->attributes['started_at'] = Carbon::now();
        }
    }

    /**
     * Calculer le temps écoulé depuis le début de la préparation
     */
    public function getElapsedMinutesAttribute()
    {
        // Si la commande n'est pas en cours ou n'a pas de début, retourner 0
        if ($this->status !== 'en_cours' || !$this->started_at) {
            return 0;
        }

        try {
            $startedAt = $this->getStartedAtAttribute($this->attributes['started_at'] ?? null);
            if (!$startedAt) {
                return 0;
            }

            $elapsed = now()->diffInMinutes($startedAt);
            
            // Limiter au temps estimé si dépassé
            if ($this->estimated_time && $elapsed > $this->estimated_time) {
                return $this->estimated_time;
            }

            return $elapsed;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Calculer le temps restant
     */
    public function getRemainingMinutesAttribute()
    {
        if (!$this->estimated_time || $this->status !== 'en_cours') {
            return 0;
        }

        $elapsed = $this->elapsed_minutes;
        return max(0, $this->estimated_time - $elapsed);
    }

    /**
     * Vérifier si le timer est actif
     */
    public function getTimerActiveAttribute()
    {
        return $this->status === 'en_cours' && 
               $this->estimated_time > 0 && 
               $this->started_at && 
               $this->remaining_minutes > 0;
    }

    /**
     * Formater le temps écoulé pour l'affichage
     */
    public function getFormattedElapsedTimeAttribute()
    {
        $minutes = $this->elapsed_minutes;
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours > 0) {
            return sprintf('%dh %02dm', $hours, $remainingMinutes);
        }

        return sprintf('%dm', $minutes);
    }

    /**
     * Formater le temps restant pour l'affichage
     */
    public function getFormattedRemainingTimeAttribute()
    {
        $minutes = $this->remaining_minutes;
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours > 0) {
            return sprintf('%dh %02dm', $hours, $remainingMinutes);
        }

        return sprintf('%dm', $minutes);
    }

    /**
     * Pourcentage de progression du timer
     */
    public function getTimerProgressPercentageAttribute()
    {
        if (!$this->estimated_time || $this->estimated_time === 0) {
            return 0;
        }

        $elapsed = $this->elapsed_minutes;
        return min(100, ($elapsed / $this->estimated_time) * 100);
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

    /**
     * Scope pour les commandes en cours
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'en_cours');
    }

    /**
     * Scope pour les commandes prêtes
     */
    public function scopeReady($query)
    {
        return $query->where('status', 'prêt');
    }

    /**
     * Scope pour les commandes terminées
     */
    public function scopeCompleted($query)
    {
        return $query->whereIn('status', ['terminé', 'livré']);
    }

    /**
     * Vérifier si la commande peut recevoir des ajouts de temps
     */
    public function canAddTime(): bool
    {
        return $this->status === 'en_cours' && 
               $this->started_at && 
               $this->estimated_time > 0 && 
               $this->hasRecentAdditions();
    }

    /**
     * Ajouter du temps à la commande (utilisé par l'admin)
     */
    public function addTime(int $additionalMinutes): bool
    {
        if (!$this->canAddTime()) {
            return false;
        }

        $this->estimated_time += $additionalMinutes;
        return $this->save();
    }

    /**
     * Démarrer le timer (mettre la commande en cours)
     */
    public function startTimer(int $estimatedMinutes = null): bool
    {
        if ($this->status !== 'commandé') {
            return false;
        }

        $this->status = 'en_cours';
        $this->started_at = Carbon::now();
        
        if ($estimatedMinutes) {
            $this->estimated_time = $estimatedMinutes;
        }

        return $this->save();
    }

    /**
     * Marquer la commande comme prête
     */
    public function markAsReady(): bool
    {
        $this->status = 'prêt';
        $this->marked_ready_at = Carbon::now();
        return $this->save();
    }

    /**
     * Relation avec les articles de commande
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Relation avec les paiements
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Obtenir le dernier paiement vérifié
     */
    public function getLastVerifiedPaymentAttribute()
    {
        return $this->payments()
            ->where('status', 'verified')
            ->latest()
            ->first();
    }

    /**
     * Obtenir le total payé
     */
    public function getTotalPaidAttribute()
    {
        return $this->payments()
            ->where('status', 'verified')
            ->sum('amount');
    }

    /**
     * Vérifier si la commande est entièrement payée
     */
    public function getIsFullyPaidAttribute()
    {
        return $this->total_paid >= $this->total;
    }

    /**
     * Formater le montant total
     */
    public function getFormattedTotalAttribute()
    {
        return number_format($this->total, 0, ',', ' ') . ' FCFA';
    }

    /**
     * Obtenir le nom du statut formaté
     */
    public function getFormattedStatusAttribute()
    {
        $statuses = [
            'commandé' => 'Commandé',
            'en_cours' => 'En cours',
            'prêt' => 'Prêt',
            'terminé' => 'Terminé',
            'livré' => 'Livré',
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * Obtenir la couleur du statut pour l'affichage
     */
    public function getStatusColorAttribute()
    {
        $colors = [
            'commandé' => 'yellow',
            'en_cours' => 'blue',
            'prêt' => 'green',
            'terminé' => 'gray',
            'livré' => 'purple',
        ];

        return $colors[$this->status] ?? 'gray';
    }
}
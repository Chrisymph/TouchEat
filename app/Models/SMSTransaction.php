<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SMSTransaction extends Model
{
    use HasFactory;

    protected $table = 'sms_transactions';

    protected $fillable = [
        'transaction_id',
        'sender_number',
        'receiver_number', 
        'amount',
        'network',
        'message',
        'sms_received_at',
        'status',
        'order_id',
        'verified_at'
    ];

    protected $casts = [
        'sms_received_at' => 'datetime',
        'verified_at' => 'datetime',
        'amount' => 'decimal:2'
    ];

    // Valeurs par défaut pour éviter les erreurs
    protected $attributes = [
        'transaction_id' => 'N/A',
        'network' => 'unknown',
        'receiver_number' => 'N/A',
        'status' => 'received'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Boot method pour s'assurer que toutes les valeurs requises sont définies
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // S'assurer que toutes les colonnes requises ont une valeur
            if (empty($model->transaction_id)) {
                $model->transaction_id = 'N/A';
            }
            if (empty($model->network)) {
                $model->network = 'unknown';
            }
            if (empty($model->receiver_number)) {
                $model->receiver_number = 'N/A';
            }
            if (empty($model->status)) {
                $model->status = 'received';
            }
            if (empty($model->sms_received_at)) {
                $model->sms_received_at = now();
            }
        });
    }

    /**
     * Trouver une transaction par ID
     */
    public static function findTransaction($transactionId, $network)
    {
        return self::where('transaction_id', $transactionId)
                  ->where('network', $network)
                  ->where('status', 'received')
                  ->first();
    }

    /**
     * Vérifier si une transaction existe et est valide
     */
    public static function isValidTransaction($transactionId, $network, $amount, $phoneNumber = null)
    {
        $query = self::where('transaction_id', $transactionId)
            ->where('network', $network)
            ->where('status', 'received')
            ->where('sms_received_at', '>=', now()->subHours(24));

        if ($phoneNumber) {
            $cleanedPhone = self::cleanPhoneForComparison($phoneNumber);
            $query->whereRaw('REPLACE(REPLACE(sender_number, " ", ""), "+", "") LIKE ?', ['%' . $cleanedPhone . '%']);
        }

        $transaction = $query->first();

        if (!$transaction) {
            return false;
        }

        // Vérification stricte du montant
        return abs($transaction->amount - $amount) <= 1;
    }

    /**
     * Nettoyer le numéro pour comparaison
     */
    private static function cleanPhoneForComparison($phone)
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        
        // Gérer les numéros avec indicatif 225
        if (strlen($cleaned) === 12 && substr($cleaned, 0, 3) === '225') {
            $cleaned = '0' . substr($cleaned, 3);
        }
        
        // Gérer les numéros avec indicatif +225
        if (strlen($cleaned) === 13 && substr($cleaned, 0, 4) === '2250') {
            $cleaned = '0' . substr($cleaned, 4);
        }
        
        // S'assurer d'avoir un format 10 chiffres
        if (strlen($cleaned) === 9) {
            $cleaned = '0' . $cleaned;
        }
        
        return $cleaned;
    }

    /**
     * Trouver une transaction valide
     */
    public static function findValidTransaction($transactionId, $network, $amount = null, $phoneNumber = null)
    {
        $query = self::where('transaction_id', $transactionId)
            ->where('network', $network)
            ->where('status', 'received')
            ->where('sms_received_at', '>=', now()->subHours(24));

        if ($amount !== null) {
            $query->whereBetween('amount', [$amount - 1, $amount + 1]);
        }

        if ($phoneNumber) {
            $cleanedPhone = self::cleanPhoneForComparison($phoneNumber);
            $query->whereRaw('REPLACE(REPLACE(sender_number, " ", ""), "+", "") LIKE ?', ['%' . $cleanedPhone . '%']);
        }

        return $query->first();
    }

    /**
     * Marquer comme utilisée
     */
    public function markAsUsed($orderId)
    {
        $this->update([
            'status' => 'used',
            'order_id' => $orderId,
            'verified_at' => now()
        ]);
    }

    /**
     * Scope pour les transactions en attente
     */
    public function scopePending($query)
    {
        return $query->where('status', 'received');
    }

    /**
     * Scope pour les transactions récentes (moins d'1 heure)
     */
    public function scopeRecent($query)
    {
        return $query->where('sms_received_at', '>=', now()->subHour());
    }

    /**
     * Scope pour les transactions par réseau
     */
    public function scopeByNetwork($query, $network)
    {
        return $query->where('network', $network);
    }

    /**
     * Scope pour les transactions par numéro d'expéditeur
     */
    public function scopeBySender($query, $senderNumber)
    {
        return $query->where('sender_number', $senderNumber);
    }

    /**
     * Vérifier si la transaction est expirée
     */
    public function isExpired()
    {
        return $this->sms_received_at->diffInHours(now()) > 24;
    }

    /**
     * Marquer comme expirée
     */
    public function markAsExpired()
    {
        if ($this->status === 'received' && $this->isExpired()) {
            $this->update(['status' => 'expired']);
        }
    }

    /**
     * Obtenir les transactions non associées
     */
    public static function getUnassociatedTransactions()
    {
        return self::whereNull('order_id')
                  ->where('status', 'received')
                  ->where('sms_received_at', '>=', now()->subHours(24))
                  ->get();
    }

    /**
     * Obtenir les statistiques des SMS
     */
    public static function getStats()
    {
        return [
            'total' => self::count(),
            'pending' => self::where('status', 'received')->count(),
            'used' => self::where('status', 'used')->count(),
            'expired' => self::where('status', 'expired')->count(),
            'today' => self::whereDate('created_at', today())->count(),
        ];
    }

    /**
     * Rechercher par ID de transaction dans le message (nouvelle méthode)
     */
    public static function findByTransactionIdInMessage($transactionId)
    {
        return self::where('message', 'LIKE', '%' . $transactionId . '%')
                  ->where('status', 'received')
                  ->where('sms_received_at', '>=', now()->subHours(24))
                  ->first();
    }
}
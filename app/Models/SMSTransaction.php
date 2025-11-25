<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SMSTransaction extends Model
{
    use HasFactory;

    // SPÉCIFIER EXPLICITEMENT LE NOM DE LA TABLE
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

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Trouver une transaction par ID
     */
    public static function findTransaction($transactionId, $network)
    {
        return self::where('transaction_id', $transactionId)
                  ->where('network', $network)
                  ->where('status', 'pending')
                  ->first();
    }

    /**
     * Vérifier si une transaction existe et est valide
     */
    public static function isValidTransaction($transactionId, $network, $amount, $phoneNumber = null)
    {
        $query = self::where('transaction_id', $transactionId)
            ->where('network', $network)
            ->where('status', 'pending')
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
        return preg_replace('/[^0-9]/', '', $phone);
    }

    /**
     * Trouver une transaction valide
     */
    public static function findValidTransaction($transactionId, $network, $amount = null, $phoneNumber = null)
    {
        $query = self::where('transaction_id', $transactionId)
            ->where('network', $network)
            ->where('status', 'pending')
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
        return $query->where('status', 'pending');
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
        if ($this->status === 'pending' && $this->isExpired()) {
            $this->update(['status' => 'expired']);
        }
    }

    /**
     * Obtenir les transactions non associées
     */
    public static function getUnassociatedTransactions()
    {
        return self::whereNull('order_id')
                  ->where('status', 'pending')
                  ->where('sms_received_at', '>=', now()->subHours(24))
                  ->get();
    }
}
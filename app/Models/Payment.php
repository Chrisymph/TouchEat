<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'amount',
        'payment_method',
        'transaction_id',
        'network',
        'phone_number',
        'status',
        'verified_at',
        'verified_by'
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'amount' => 'decimal:2'
    ];

    // Statuts simplifiés
    const STATUS_PENDING = 'pending';
    const STATUS_VERIFIED = 'verified';
    const STATUS_REJECTED = 'rejected';

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Vérification semi-automatique simplifiée - CORRIGÉE
     */
    public function attemptAutoVerification()
    {
        try {
            // 1. Validation basique du format
            if (!$this->isValidTransactionIdFormat()) {
                \Log::info("Échec validation format ID: {$this->transaction_id}");
                return false;
            }

            // 2. Vérification du montant
            if (!$this->isAmountMatching()) {
                \Log::info("Échec validation montant: {$this->amount} vs {$this->order->total}");
                return false;
            }

            // 3. Vérification des doublons
            if ($this->isTransactionIdDuplicate()) {
                \Log::info("Échec validation doublon: {$this->transaction_id}");
                return false;
            }

            // Si toutes les vérifications passent, marquer comme vérifié
            $this->update([
                'status' => self::STATUS_VERIFIED,
                'verified_at' => now()
            ]);

            // CORRECTION : Mettre à jour le statut de la commande
            if ($this->order) {
                $this->order->update(['payment_status' => 'payé']);
                \Log::info("✅ Statut commande mis à jour: commande #{$this->order->id} -> payé");
            }

            return true;

        } catch (\Exception $e) {
            \Log::error('Erreur attemptAutoVerification:', ['error' => $e->getMessage(), 'payment_id' => $this->id]);
            return false;
        }
    }

    /**
     * Validation du format de l'ID de transaction
     */
    private function isValidTransactionIdFormat()
    {
        $transactionId = $this->transaction_id;
        
        // Format général: mélange de chiffres et lettres, longueur 6-20 caractères
        return preg_match('/^[A-Z0-9]{6,20}$/i', $transactionId);
    }

    /**
     * Vérification du montant
     */
    private function isAmountMatching()
    {
        return abs($this->amount - $this->order->total) < 0.01;
    }

    /**
     * Vérification des doublons
     */
    private function isTransactionIdDuplicate()
    {
        return self::where('transaction_id', $this->transaction_id)
            ->where('network', $this->network)
            ->where('status', self::STATUS_VERIFIED)
            ->where('id', '!=', $this->id)
            ->exists();
    }
}
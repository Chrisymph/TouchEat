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
            if (empty($model->transaction_id) || $model->transaction_id === 'N/A') {
                // Essayer d'extraire le transaction_id du message
                $transactionId = self::extractTransactionIdFromMessage($model->message);
                $model->transaction_id = $transactionId ?: 'N/A';
            }
            
            if (empty($model->network) || $model->network === 'unknown') {
                $model->network = self::detectNetworkFromMessage($model->message, $model->sender_number);
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
            
            // Si amount est null, essayer de l'extraire du message
            if (is_null($model->amount)) {
                $amount = self::extractAmountFromMessage($model->message);
                if ($amount !== null) {
                    $model->amount = $amount;
                }
            }
        });
    }

    /**
     * Extraire le transaction_id du message
     */
    private static function extractTransactionIdFromMessage($message)
    {
        // Pattern pour Moov Money Ref
        if (preg_match('/Ref\s*:?\s*([A-Z0-9]{8,20})/i', $message, $matches)) {
            return trim($matches[1]);
        }
        
        // Pattern pour Txn ID
        if (preg_match('/Txn ID:\s*([A-Z0-9]+)/i', $message, $matches)) {
            return trim($matches[1]);
        }
        
        return null;
    }

    /**
     * Détecter le réseau depuis le message
     */
    private static function detectNetworkFromMessage($message, $senderNumber)
    {
        $message = strtolower($message);
        
        if (strpos($message, 'mtn') !== false) {
            return 'mtn';
        }
        
        if (strpos($message, 'moov') !== false) {
            return 'moov';
        }
        
        if (strpos($message, 'orange') !== false) {
            return 'orange';
        }
        
        // Détection par expéditeur
        if (strpos($senderNumber, 'moov') !== false) {
            return 'moov';
        }
        
        return 'moov'; // Par défaut
    }

    /**
     * Extraire le montant du message
     */
    private static function extractAmountFromMessage($message)
    {
        if (preg_match('/(\d+(?:[.,;\s]\d+)*)\s*FCFA/i', $message, $matches)) {
            return floatval(str_replace([' ', ',', ';'], ['', '.', '.'], $matches[1]));
        }
        
        return null;
    }

    /**
     * Trouver une transaction par ID exact
     */
    public static function findExactTransaction($transactionId, $network = null)
    {
        $query = self::where('transaction_id', $transactionId)
            ->where('status', 'received')
            ->where('sms_received_at', '>=', now()->subHours(72));

        if ($network) {
            $query->where('network', $network);
        }

        return $query->first();
    }

    /**
     * Vérifier si une transaction existe et est valide
     */
    public static function isValidTransaction($transactionId, $network, $amount, $phoneNumber = null)
    {
        $transaction = self::findExactTransaction($transactionId, $network);
        
        if (!$transaction) {
            return false;
        }

        // Vérification du montant
        if (abs($transaction->amount - $amount) > 0.5) {
            return false;
        }

        // Vérification du numéro de téléphone si fourni
        if ($phoneNumber) {
            return $transaction->phoneNumberMatches($phoneNumber);
        }

        return true;
    }

    /**
     * Nettoyer le numéro pour comparaison
     */
    private static function cleanPhoneForComparison($phone)
    {
        if (empty($phone)) return '';
        
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        
        // Gérer les numéros avec indicatif 225
        if (strlen($cleaned) === 12 && substr($cleaned, 0, 3) === '225') {
            $cleaned = '0' . substr($cleaned, 3);
        }
        
        // Retourner les 10 derniers chiffres
        if (strlen($cleaned) > 10) {
            $cleaned = substr($cleaned, -10);
        }
        
        return $cleaned;
    }

    /**
     * Vérifier si le numéro de téléphone correspond à la transaction
     */
    public function phoneNumberMatches($phoneNumber)
    {
        $cleanedPhone = self::cleanPhoneForComparison($phoneNumber);
        
        if (empty($cleanedPhone)) return false;

        // 1. Vérifier dans l'expéditeur
        $cleanedSender = self::cleanPhoneForComparison($this->sender_number);
        if ($cleanedSender && self::comparePhones($cleanedSender, $cleanedPhone)) {
            return true;
        }

        // 2. Vérifier dans le message
        if (strpos($this->message, $cleanedPhone) !== false) {
            return true;
        }

        // 3. Extraire tous les numéros du message et vérifier
        $phonesInMessage = self::extractPhonesFromMessage($this->message);
        foreach ($phonesInMessage as $phoneInMessage) {
            if (self::comparePhones($phoneInMessage, $cleanedPhone)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Comparer deux numéros de téléphone
     */
    private static function comparePhones($phone1, $phone2)
    {
        if (!$phone1 || !$phone2) return false;

        // Comparaison exacte
        if ($phone1 === $phone2) return true;

        // Comparaison des 8 derniers chiffres
        if (strlen($phone1) >= 8 && strlen($phone2) >= 8) {
            return substr($phone1, -8) === substr($phone2, -8);
        }

        return false;
    }

    /**
     * Extraire les numéros de téléphone d'un message
     */
    private static function extractPhonesFromMessage($message)
    {
        $phones = [];
        preg_match_all('/\b\d{8,15}\b/', $message, $matches);
        
        foreach ($matches[0] as $phone) {
            $cleaned = self::cleanPhoneForComparison($phone);
            if (strlen($cleaned) >= 8) {
                $phones[] = $cleaned;
            }
        }
        
        return array_unique($phones);
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
     * Scope pour les transactions récentes
     */
    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('sms_received_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope pour les transactions par réseau
     */
    public function scopeByNetwork($query, $network)
    {
        return $query->where('network', $network);
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
            'last_24h' => self::where('sms_received_at', '>=', now()->subHours(24))->count(),
        ];
    }
}
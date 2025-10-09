<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'start_date',
        'end_date',
        'data',
        'description',
        'is_generated'
    ];

    protected $casts = [
        'data' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_generated' => 'boolean'
    ];

    /**
     * Scope pour les rapports générés
     */
    public function scopeGenerated($query)
    {
        return $query->where('is_generated', true);
    }

    /**
     * Scope pour les rapports par type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope pour les rapports dans une période
     */
    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->where('start_date', '>=', $startDate)
                    ->where('end_date', '<=', $endDate);
    }

    /**
     * Générer un nom de rapport automatique
     */
    public static function generateName($type, $startDate, $endDate)
    {
        $typeNames = [
            'daily' => 'Rapport Quotidien',
            'weekly' => 'Rapport Hebdomadaire',
            'monthly' => 'Rapport Mensuel',
            'custom' => 'Rapport Personnalisé'
        ];

        $name = $typeNames[$type] ?? 'Rapport';
        
        if ($startDate->eq($endDate)) {
            $name .= ' du ' . $startDate->format('d/m/Y');
        } else {
            $name .= ' du ' . $startDate->format('d/m/Y') . ' au ' . $endDate->format('d/m/Y');
        }

        return $name;
    }
}
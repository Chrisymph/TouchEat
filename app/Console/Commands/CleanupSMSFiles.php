<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupSMSFiles extends Command
{
    protected $signature = 'sms:cleanup-old-files';
    protected $description = 'Nettoyer les anciens fichiers SMS traités';

    public function handle()
    {
        $this->info('🧹 Nettoyage des anciens fichiers SMS...');

        try {
            $smsDirectory = storage_path('app/mobiletrans_sms');
            
            if (!file_exists($smsDirectory)) {
                $this->info('Aucun dossier à nettoyer');
                return;
            }

            $files = glob($smsDirectory . '/*.processed');
            $deletedCount = 0;

            foreach ($files as $file) {
                // Supprimer les fichiers traités de plus de 7 jours
                if (filemtime($file) < strtotime('-7 days')) {
                    unlink($file);
                    $deletedCount++;
                }
            }

            $this->info("✅ {$deletedCount} anciens fichiers supprimés");
            Log::info("Nettoyage SMS: {$deletedCount} fichiers supprimés");

        } catch (\Exception $e) {
            $this->error('❌ Erreur de nettoyage: ' . $e->getMessage());
            Log::error('Erreur cleanup SMS files:', ['error' => $e->getMessage()]);
        }
    }
}
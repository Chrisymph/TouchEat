<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ClientController;
use Illuminate\Support\Facades\Log;

class SyncSMSFiles extends Command
{
    protected $signature = 'sms:sync-files';
    protected $description = 'Synchroniser automatiquement les fichiers SMS depuis MobileTrans';

    public function handle()
    {
        $this->info('🔄 Synchronisation automatique des fichiers SMS...');

        try {
            $clientController = new ClientController();
            
            // Utiliser la réflexion pour appeler la méthode privée
            $reflection = new \ReflectionClass($clientController);
            $method = $reflection->getMethod('syncAllSMSFiles');
            $method->setAccessible(true);
            
            $result = $method->invoke($clientController);
            
            $this->info("✅ {$result['message']}");
            Log::info("Synchronisation automatique SMS: {$result['message']}");

        } catch (\Exception $e) {
            $this->error('❌ Erreur de synchronisation: ' . $e->getMessage());
            Log::error('Erreur sync SMS files:', ['error' => $e->getMessage()]);
        }
    }
}
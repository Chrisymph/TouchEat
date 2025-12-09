<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ClientController;
use Illuminate\Support\Facades\Log;

class ForceSmsSync extends Command
{
    protected $signature = 'sms:force-sync';
    protected $description = 'Forcer la synchronisation de tous les fichiers SMS';

    public function handle()
    {
        $this->info('🔄 Synchronisation forcée des SMS...');

        try {
            $controller = new ClientController();
            
            // Utiliser la réflexion pour appeler la méthode privée
            $reflection = new \ReflectionClass($controller);
            $method = $reflection->getMethod('syncAllSMSFiles');
            $method->setAccessible(true);
            
            $result = $method->invoke($controller);
            
            $this->info("✅ {$result['message']}");
            $this->info("📊 {$result['imported']} SMS importés");
            
            // Afficher les stats
            $stats = \App\Models\SMSTransaction::getStats();
            $this->info("📈 Statistiques:");
            $this->info("   Total SMS: {$stats['total']}");
            $this->info("   En attente: {$stats['pending']}");
            $this->info("   Utilisés: {$stats['used']}");
            $this->info("   Dernières 24h: {$stats['last_24h']}");

        } catch (\Exception $e) {
            $this->error('❌ Erreur: ' . $e->getMessage());
            Log::error('Erreur sync forcée:', ['error' => $e->getMessage()]);
        }
    }
}
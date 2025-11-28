<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SMSTransaction;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\ClientController;

class SyncMobileTransCommand extends Command
{
    protected $signature = 'mobiletrans:sync';
    protected $description = 'Synchroniser les SMS depuis Wondershare MobileTrans';

    public function handle()
    {
        $this->info('🔄 Synchronisation MobileTrans...');

        try {
            $clientController = new ClientController();
            $result = $clientController->syncWithMobileTransFiles();
            
            if ($result['success']) {
                $this->info("✅ {$result['message']}");
                Log::info("Synchronisation automatique MobileTrans: {$result['message']}");
            } else {
                $this->error("❌ {$result['message']}");
                Log::error("Erreur sync MobileTrans: {$result['message']}");
            }

        } catch (\Exception $e) {
            $this->error('❌ Erreur de synchronisation: ' . $e->getMessage());
            Log::error('Erreur sync MobileTrans:', ['error' => $e->getMessage()]);
        }
    }
}
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SMSTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DebugSmsImport extends Command
{
    protected $signature = 'sms:debug';
    protected $description = 'Debuguer l\'importation des SMS';

    public function handle()
    {
        $this->info('🔍 Debug importation SMS');

        // 1. Vérifier le fichier CSV
        $smsDirectory = storage_path('app/mobiletrans_sms');
        $files = glob($smsDirectory . '/*.csv');
        
        $this->info("📂 Fichiers CSV trouvés: " . count($files));
        
        foreach ($files as $file) {
            $this->info("📄 Fichier: " . basename($file));
            
            // Lire les premières lignes du CSV
            if (($handle = fopen($file, 'r')) !== FALSE) {
                $header = fgetcsv($handle, 1000, ',');
                $this->info("📊 En-tête: " . implode(', ', $header));
                
                $lineCount = 0;
                while (($data = fgetcsv($handle, 3000, ',')) !== FALSE && $lineCount < 5) {
                    $lineCount++;
                    $this->info("📝 Ligne {$lineCount}: " . substr(implode(' | ', $data), 0, 200));
                }
                fclose($handle);
            }
        }

        // 2. Vérifier la base de données
        $this->info("\n📊 BASE DE DONNÉES:");
        $totalSMS = SMSTransaction::count();
        $this->info("Total SMS: {$totalSMS}");
        
        $recentSMS = SMSTransaction::orderBy('id', 'desc')->limit(10)->get();
        $this->info("🔍 Derniers SMS:");
        
        foreach ($recentSMS as $sms) {
            $this->info("ID: {$sms->id} | Ref: {$sms->transaction_id} | Montant: {$sms->amount} | " . substr($sms->message, 0, 50));
        }

        // 3. Rechercher spécifiquement le SMS problématique
        $this->info("\n🎯 RECHERCHE SMS 030812360189:");
        $target = SMSTransaction::where('transaction_id', '030812360189')->first();
        
        if ($target) {
            $this->info("✅ TROUVÉ! ID: {$target->id}");
            $this->info("Message: " . $target->message);
        } else {
            $this->error("❌ NON TROUVÉ!");
            
            // Chercher des SMS similaires
            $similar = SMSTransaction::where('message', 'LIKE', '%030812360189%')->first();
            if ($similar) {
                $this->info("ℹ️  Mais trouvé dans le message: ID {$similar->id}");
                $this->info("Message: " . $similar->message);
            }
        }
    }
}
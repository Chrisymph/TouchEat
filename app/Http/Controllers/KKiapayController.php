<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Kkiapay\Kkiapay;

class KkiapayController extends Controller
{
    /**
     * Client KKiaPay (SDK officiel kkiapay/kkiapay-php)
     */
    private function kkiapay(): Kkiapay
    {
        return new Kkiapay(
            config('services.kkiapay.public_key'),
            config('services.kkiapay.private_key'),
            config('services.kkiapay.secret'),
            (bool) config('services.kkiapay.sandbox')
        );
    }

    /**
     * Page de paiement : affiche le widget KKiaPay
     */
    public function show($orderId, $paymentId)
    {
        if (!Auth::check() || Auth::user()->role !== 'client') {
            return redirect()->route('client.auth');
        }

        $user = Auth::user();
        if ($user->isSuspended()) {
            Auth::logout();
            return redirect()->route('client.auth')->with('error', 'Votre compte a été suspendu. Veuillez contacter l\'administrateur.');
        }

        $order = Order::where('id', $orderId)
            ->where('table_number', $user->table_number)
            ->firstOrFail();

        $payment = Payment::where('id', $paymentId)
            ->where('order_id', $order->id)
            ->firstOrFail();

        // Déjà payé : on reprend directement le workflow normal
        if ($payment->status === Payment::STATUS_VERIFIED) {
            return redirect()->route('client.order.confirmation', $order->id);
        }

        // Paiement additionnel = la commande avait déjà un paiement validé
        $isAdditional = $order->payments()
            ->where('status', Payment::STATUS_VERIFIED)
            ->exists();

        return view('client.kkiapay-payment', [
            'tableNumber'  => $user->table_number,
            'order'        => $order,
            'payment'      => $payment,
            'amountToPay'  => $payment->amount,
            'isAdditional' => $isAdditional,
        ]);
    }

    /**
     * Appelé en AJAX après le succès du widget : vérification côté serveur
     */
    public function verify(Request $request, $orderId, $paymentId)
    {
        if (!Auth::check() || Auth::user()->role !== 'client') {
            return response()->json(['success' => false, 'message' => 'Non authentifié'], 401);
        }

        $user = Auth::user();
        if ($user->isSuspended()) {
            return response()->json(['success' => false, 'message' => 'Votre compte a été suspendu.'], 403);
        }

        $request->validate([
            'transaction_id' => 'required|string|max:100',
        ]);

        $order = Order::where('id', $orderId)
            ->where('table_number', $user->table_number)
            ->firstOrFail();

        $payment = Payment::where('id', $paymentId)
            ->where('order_id', $order->id)
            ->firstOrFail();

        $result = $this->settle($payment, $request->transaction_id);

        return response()->json([
            'success'      => $result['ok'],
            'message'      => $result['message'],
            'redirect_url' => $result['ok'] ? route('client.order.confirmation', $order->id) : null,
        ], $result['ok'] ? 200 : 422);
    }

    /**
     * Webhook KKiaPay (filet de sécurité si le client ferme la page)
     * Route publique, exclue du CSRF.
     */
    public function webhook(Request $request)
    {
        $expected = (string) config('services.kkiapay.webhook_secret');
        $received = (string) $request->header('x-kkiapay-secret');

        if ($expected === '' || !hash_equals($expected, $received)) {
            Log::warning('KKiaPay webhook: signature invalide');
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        Log::info('KKiaPay webhook reçu', $payload);

        $transactionId = data_get($payload, 'transactionId') ?? data_get($payload, 'transaction_id');
        if (!$transactionId) {
            return response()->json(['received' => true]);
        }

        // "data" passé au widget = id du paiement (renvoyé dans stateData)
        $paymentId = data_get($payload, 'stateData');
        $payment = is_numeric($paymentId)
            ? Payment::find((int) $paymentId)
            : Payment::where('transaction_id', $transactionId)->first();

        if (!$payment) {
            Log::warning('KKiaPay webhook: paiement introuvable', ['transactionId' => $transactionId, 'stateData' => $paymentId]);
            return response()->json(['received' => true]);
        }

        $this->settle($payment, (string) $transactionId);

        // Toujours répondre 200 pour que KKiaPay arrête les relances
        return response()->json(['received' => true]);
    }

    /**
     * Vérifie la transaction auprès de KKiaPay puis valide le paiement.
     * Utilisée par verify() ET webhook() : idempotente.
     */
    private function settle(Payment $payment, string $transactionId): array
    {
        try {
            $tx = $this->kkiapay()->verifyTransaction($transactionId);
        } catch (\Throwable $e) {
            Log::error('KKiaPay verifyTransaction: erreur', [
                'payment_id' => $payment->id,
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);
            return ['ok' => false, 'message' => 'Impossible de vérifier le paiement pour le moment. Réessayez dans un instant.'];
        }

        Log::info('KKiaPay verifyTransaction', [
            'payment_id' => $payment->id,
            'tx' => json_decode(json_encode($tx), true),
        ]);

        $status = strtoupper((string) data_get($tx, 'status'));
        $amount = (float) data_get($tx, 'amount');

        if ($status === 'PENDING') {
            return ['ok' => false, 'message' => 'Paiement en cours de traitement. Il sera confirmé automatiquement.'];
        }

        if ($status !== 'SUCCESS') {
            return ['ok' => false, 'message' => 'Le paiement n\'a pas abouti. Vous n\'avez pas été débité ou vous serez remboursé.'];
        }

        return DB::transaction(function () use ($payment, $transactionId, $amount) {
            // Verrou pour éviter le double traitement (vérification client + webhook en parallèle)
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->first();

            if ($payment->status === Payment::STATUS_VERIFIED) {
                return ['ok' => true, 'message' => 'Paiement déjà confirmé.'];
            }

            // Le montant payé doit couvrir le montant attendu
            if ($amount < (float) $payment->amount) {
                Log::warning('KKiaPay: montant insuffisant', [
                    'payment_id' => $payment->id,
                    'attendu' => (float) $payment->amount,
                    'recu' => $amount,
                ]);
                return ['ok' => false, 'message' => 'Le montant payé ne correspond pas au montant de la commande.'];
            }

            // Une transaction ne peut servir qu'une seule fois
            $alreadyUsed = Payment::where('transaction_id', $transactionId)
                ->where('status', Payment::STATUS_VERIFIED)
                ->where('id', '!=', $payment->id)
                ->exists();

            if ($alreadyUsed) {
                return ['ok' => false, 'message' => 'Cette transaction a déjà été utilisée pour une autre commande.'];
            }

            $payment->update([
                'transaction_id' => $transactionId,
                'status'         => Payment::STATUS_VERIFIED,
                'verified_at'    => now(),
            ]);

            // La commande passe à "payé" quand les paiements validés couvrent son total
            // (un paiement additionnel sur une commande déjà payée ne change rien)
            $order = Order::find($payment->order_id);
            if ($order && (float) $order->total_paid >= (float) $order->total) {
                $order->update(['payment_status' => 'payé']);
            }

            return ['ok' => true, 'message' => 'Paiement confirmé ! Votre commande est en cours de préparation.'];
        });
    }
}
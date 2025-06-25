<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;
use Illuminate\Support\Facades\Log;
use App\Models\Factura;

class ProcesarPagoMercadoPago implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Laravel reintentará automáticamente hasta 8 veces
    public $tries   = 8;
    // Backoff exponencial (segundos)
    public $backoff = [5, 10, 20, 40, 80, 160, 320, 640];

    protected $paymentId;
    protected $status;
    protected $externalReference;

    /**
     * Crear una nueva instancia del Job.
     *
     * @param  string       $paymentId
     * @param  string|null  $status
     * @param  string|null  $externalReference
     */
    public function __construct(string $paymentId, ?string $status = null, ?string $externalReference = null)
    {
        $this->paymentId         = $paymentId;
        $this->status            = $status;
        $this->externalReference = $externalReference;
    }

    /**
     * Ejecutar el Job.
     */
    public function handle(): void
    {
        Log::info("Access token usado: " . env('MP_ACCESS_TOKEN'));
        MercadoPagoConfig::setAccessToken(env('MP_ACCESS_TOKEN'));

        // Si no tengo status o external_reference, obtengo el objeto completo
        if (! $this->status || ! $this->externalReference) {
            try {
                $payment = (new PaymentClient())->get($this->paymentId);
                $this->status            = $payment->status;
                $this->externalReference = $payment->external_reference;
            } catch (MPApiException $e) {
                $code    = optional($e->getApiResponse())->getStatusCode();
                $content = optional($e->getApiResponse())->getContent() ?: $e->getMessage();
                Log::warning("Intento fallo (ID={$this->paymentId}) – Code {$code}: {$content}");
                // Relanzar para que Laravel reintente según $backoff
                throw $e;
            }
        }

        Log::info("Procesando pago {$this->paymentId} – status={$this->status}, ext_ref={$this->externalReference}");

        // Sólo marco la factura cuando esté aprobado
        if ($this->status === 'approved' && $this->externalReference) {
            $factura = Factura::find($this->externalReference);
            if ($factura && ! $factura->pagado) {
                $factura->update(['pagado' => true]);
                Log::info("✅ Factura {$factura->id} marcada como pagada.");
            }
        }
        // Opcional: manejar rechazados/refunds/contracargos
        elseif (in_array($this->status, ['rejected', 'refunded', 'cancelled'])) {
            Log::warning("⚠️ Pago {$this->paymentId} en estado {$this->status}.");
            // Aquí podrías notificar al cliente, revertir stock, etc.
        } else {
            Log::info("ℹ️ Pago {$this->paymentId} en estado intermedio ({$this->status}).");
        }
    }
}

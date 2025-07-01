<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\PayPalService;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use App\Models\Tomo;
use App\Models\Factura;
use App\Models\DetalleFactura;

class PayPalController extends Controller
{
    // 1) Crear la orden y guardar carrito en sesión
    public function createOrder(Request $request)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['message'=>'No autenticado'],401);

        $items = $request->input('items', []);
        session(['paypal_cart' => $items]);

        // Calcular total real (ARS)
        $total = 0;
        foreach ($items as $i) {
            $t = Tomo::findOrFail($i['id']);
            $total += $t->precio * $i['quantity'];
        }

        $client = PayPalService::client();
        $orderReq = new OrdersCreateRequest();
        $orderReq->prefer('return=representation');
        $orderReq->body = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => 'ARS',
                    'value'         => number_format($total,2,'.',''),
                ]
            ]],
            'application_context' => [
                'return_url' => env('APP_URL').'/paypal/success',
                'cancel_url' => env('APP_URL').'/paypal/cancel',
            ]
        ];

        $resp = $client->execute($orderReq);
        $approvalUrl = collect($resp->result->links)
            ->firstWhere('rel','approve')->href;

        return response()->json(['approval_url'=>$approvalUrl]);
    }

    // 2) PayPal redirige aquí tras aprobar el pago
    public function success(Request $request)
    {
        $token = $request->query('token');
        if (!$token) return redirect('/?error=Falta token');

        $client = PayPalService::client();
        $captureReq = new OrdersCaptureRequest($token);
        $captureReq->prefer('return=representation');
        $resp = $client->execute($captureReq);

        if (($resp->result->status ?? '') !== 'COMPLETED') {
            return redirect('/?error=Pago no completado');
        }

        // Crear factura usando carrito de sesión
        $cart = session('paypal_cart', []);
        $user = Auth::user();
        $factura = Factura::create([
            'numero'     => \Str::uuid()->toString(),
            'cliente_id' => $user->id,
            'pagado'     => true,
        ]);
        foreach ($cart as $i) {
            $t = Tomo::find($i['id']);
            DetalleFactura::create([
                'factura_id'      => $factura->id,
                'tomo_id'         => $t->id,
                'cantidad'        => $i['quantity'],
                'precio_unitario' => $t->precio,
                'subtotal'        => $t->precio * $i['quantity'],
            ]);
            $t->decrement('stock', $i['quantity']);
        }
        session()->forget('paypal_cart');

        return redirect(env('APP_FRONT_URL')."/facturas/{$factura->id}");
    }

    // 3) PayPal redirige aquí si se cancela
    public function cancel()
    {
        return redirect(env('APP_FRONT_URL')."/?error=Pago cancelado");
    }
}

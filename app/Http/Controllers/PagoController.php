<?php

namespace App\Http\Controllers;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\DetallePedido;
use LaravelDaily\Invoices\Invoice;
use Illuminate\Support\Facades\Storage;
use LaravelDaily\Invoices\Classes\Party;
use LaravelDaily\Invoices\Classes\InvoiceItem;

use Illuminate\Http\Request;


class PagoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
       //
    }

    /**
     * Store a newly created resource in storage.
     */

    public function store(Request $request)
    {
        try {

            $request->validate([
                'monto' => 'required|numeric|min:0',
                'estado' => 'required|in:pendiente,pagado,rechazado',
                'metodo_pago_id' => 'required|exists:metodo_pagos,id',
                'pedido_id' => 'required|exists:pedidos,id'
            ]);

            $pedido = Pedido::with(['user', 'detalles.producto'])->findOrFail($request->pedido_id);

            $pago = Pago::create([
                'monto' => $request->monto,
                'estado' => $request->estado,
                'metodo_pago_id' => $request->metodo_pago_id,
                'pedido_id' => $request->pedido_id,
            ]);

            // Si el pago se completa, actualizamos el estado del pedido a pagada
            if ($pago->estado === 'pagada') {
                $pedido->update(['estado' => 'pagada']);
            }

            $facturaUrl = null;

            if ($pago->estado === 'pagada') {

                $customer = new Party([
                    'name' => $pedido->user->name,
                    'custom_fields' => [
                        'email' => $pedido->user->email,
                    ],
                ]);

                $items = [];

                foreach ($pedido->detalles ?? [] as $detalle) {
                    $items[] = InvoiceItem::make($detalle->producto->nombre)
                        ->description($detalle->producto->descripcion ?? '')
                        ->pricePerUnit($detalle->precio_unitario)
                        ->quantity($detalle->cantidad);
                }

                // Si no hay detalles, usar monto total
                if (empty($items)) {
                    $items[] = InvoiceItem::make('Pedido #' . $pedido->id)
                        ->pricePerUnit($pago->monto);
                }

                $filename = 'factura-' . ($pedido->correlativo ?? $pedido->id);

                $invoice = Invoice::make('Factura')
                    ->serialNumberFormat('FAC-{SEQUENCE}')
                    ->sequence($pedido->id)
                    ->buyer($customer)
                    ->seller('Zona Cero')
                    ->taxRate(0)
                    ->discountByPercent(0)
                    ->addItems($items)
                    ->filename($filename);

                $path = $invoice->save('public/facturas');
                $facturaUrl = Storage::url($path);
            }

            return response()->json([
                'success' => true,
                'pago' => $pago,
                'factura_url' => $facturaUrl
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }

      /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}



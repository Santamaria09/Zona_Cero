<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Producto;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try{
            $query = Order::with(['user', 'items.producto', 'pagos']);
            //filtramos
            if($request->estado){
                $query->where('estado', $request->estado);
            }
            //ordenes generadas anteriormente
            if($request->user_id){
                $query->where('user_id', $request->user_id);
            }

            //definir la orden a mostrar

            $order = $query->orderBy('fecha', 'desc')->get();
            return response()->json($order);
        } catch(\Exception $e){
            return response()->json([
                'message' => 'Error al obtener la lista de ordenes',
                'error' => $e->getMessage()
            ],500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
        //validacion
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'fecha' => 'required|date',
            'subtotal' => 'required|numeric|min:0',
            'impuesto' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.producto_id' => 'required|exists:productos,id',
            'items.*.cantidad' => 'required|integer|min:1',
            
        ]);

        // iniciar transaccion
        DB::beginTransaction();
        //crear la orden
        $order = Order::create([
            'correlativo' => $this->generateCorrelativo(),
            'fecha' => $data['fecha'],
            'subtotal' => $data['subtotal'],
            'impuesto' => $data['impuesto'],
            'total' => $data['total'],
            'estado' => 'PENDIENTE',
            'user_id' => $data['user_id']
        ]);
        //RECORREMOS LA COLECCION DE ITEMS PARA AGREGAR EN ORDER_ITEMS
        foreach($data['items'] as $item){
            //OBTENEMOS EL PRODUCTO DE LA TABLA
            $producto = Producto::findOrFail($item['producto_id']);
            $subt = $producto->precio * $item['cantidad'];
            //CREAMOS EL ITEM DE LA ORDEN
            OrderItem::create([
                'cantidad' => $item['cantidad'],
                'precio_unitario' => $producto->precio,
                'subtotal' => $subt,
                'producto_id' => $item['producto_id'],
                'order_id' => $order->id
            ]);
        }
        } catch(\Exception $e){
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear la orden',
                'error'=> $e->getMessage()
            ],500);
        }

        DB::commit();
        return response()->json([
            'message' => 'Orden creada exitosamente',
            'order' => $order->load('items.producto')
        ],200);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
         try{
            $order = Order::with(['user','items.producto','pagos'])->findOrFail($id);
            return response()->json($producto);
        }catch(ModelNotFoundException $e){
            return response()->json([
                'message' => 'No se ha encontrado la order con ID = ' . $id
            ],404);
        }
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

    public function gestionarEstado(Request $request, $id){
        try{
            //obtener la orden DB 
            $order = Order::findOrFail($id);

            //validacion del estado
            $data = $request->validate([
                'estado' => 'required|in:PENDIENTE,PAGADA,CANCELADA,REEMBOLSADA,ENTREGADA'
            ]);
            
            //obtencion del nuevo estado de la orden
            $nuevoEstado = $data['estado'];
            
            $estadoActual = $order->estado;

            $transicionesValidas = [
                'PENDIENTE' => ['PAGADA', 'CANCELADA'],
                'PAGADA' => ['ENTREGADA', 'REEMBOLSADA'],
                'ENTREGADA' => ['REEMBOLSADA'],
                'CANCELADA' => [],
                'REEMBOLSADA' => []
                                
            ];
                //validacion 
            if(!in_array($nuevoEstado, $transicionesValidas[$estadoActual])){
                return response()->json([
                    'message' => "No se puede cambiar de $estadoActual a $nuevoEstado"
                ],400);
            }
        //actualizacion del estado de la orden
            $order->estado = $nuevoEstado;
            if($nuevoEstado === 'ENTREGADA'){
                $order->fecha_despacho = now();
            }
            $order->update();
            return response()->json([
                'message' => "La orden $order->correlativo ha sido actualizada a estado $nuevoEstado",
                'order' => $order->load('items.producto')
            ]);

        }catch(\Exception $e){
            return response()->json([
                'message' => 'Error al actualizar el estado de la orden',
                'error' => $e->getMessage()
            ],500);
        }
    }

    private function generateCorrelativo(){
        $year = now()->format('Y');
        $month = now()->format('m');
        $ultimo = Order::whereYear('fecha', $year)
                        ->whereMonth('fecha', $month)
                        ->lockForUpdate()
                        ->count();
                        $numero = str_pad($ultimo +1 ,4,'0', STR_PAD_LEFT);
                        return $year . $month . $numero;    
    }
}

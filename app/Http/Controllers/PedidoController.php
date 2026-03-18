<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Pedido;
use App\Models\DetallePedido;
use App\Models\Producto;

class PedidoController extends Controller
{

    public function index(Request $request)
    {
        try{

            $query = Pedido::with(['user','detalles.producto','pagos']);

            if($request->estado){
                $query->where('estado',$request->estado);
            }

            if($request->user_id){
                $query->where('user_id',$request->user_id);
            }

            $pedido = $query->orderBy('fecha_pedido','desc')->get();

            return response()->json($pedido);

        } catch(\Exception $e){

            return response()->json([
                'message'=>'Error al obtener la lista de pedidos',
                'error'=>$e->getMessage()
            ],500);

        }
    }

    public function store(Request $request)
    {
        try{

            $data = $request->validate([
                'user_id'=>'required|exists:users,id',
                'fecha_pedido'=>'required|date',
                'subtotal'=>'required|numeric|min:0',
                'impuesto'=>'required|numeric|min:0',
                'total'=>'required|numeric|min:0',
                'detalles'=>'required|array|min:1',
                'detalles.*.producto_id'=>'required|exists:productos,id',
                'detalles.*.cantidad'=>'required|numeric|min:1'
            ]);

            DB::beginTransaction();


            $pedido = Pedido::create([
                'correlativo'=>$this->generateCorrelativo(),
                'fecha_pedido'=>$data['fecha_pedido'],
                'subtotal'=>$data['subtotal'],
                'impuesto'=>$data['impuesto'],
                'total'=>$data['total'],
                'estado'=>'pendiente',
                'user_id'=>$data['user_id']
            ]);

            $alert = [];

            foreach($data['detalles'] as $detalle){

                $producto = Producto::findOrFail($detalle['producto_id']);

                if($producto->stock < $detalle['cantidad']){
                    throw new \Exception("No se puede proseguir, stock insuficiente del {$producto->nombre}");
                }

                $subtotal = $producto->precio * $detalle['cantidad'];

                DetallePedido::create([
                    'cantidad'=>$detalle['cantidad'],
                    'precio_unitario'=>$producto->precio,
                    'subtotal'=>$subtotal,
                    'producto_id'=>$detalle['producto_id'],
                    'pedido_id'=>$pedido->id
                ]);

                // actualizar stock
                $producto->decrement('stock',$detalle['cantidad']);

                $stockM = 3;
                $productActualizado = $producto ->fresh();

                if($productActualizado-> stock <= $stockM){
                    $alert[] = [
                        'producto' => $producto->nombre,
                        'stock_restante' => $productActualizado -> stock,
                        'message' => 'Stock insuficiente'
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'message'=>'Pedido creado exitosamente',
                'pedido'=>$pedido->load('detalles.producto'),
                'alert_stock' => $alert
            ],201);

        } catch(\Exception $e){

            DB::rollBack();

            return response()->json([
                'message'=>'Error al crear el pedido',
                'error'=>$e->getMessage()
            ],500);

        }
    }

    public function show(string $id)
    {
        try{

            $pedido = Pedido::with(['user','detalles.producto','pagos'])->findOrFail($id);

            return response()->json($pedido);

        } catch(ModelNotFoundException $e){

            return response()->json([
                'message'=>'No se ha encontrado el pedido con ID = '.$id
            ],404);

        }
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        //
    }

    public function gestionarEstado(Request $request,$id)
    {

        try{

            $pedido = Pedido::findOrFail($id);

            $data = $request->validate([
                'estado'=>'required|in:pendiente,pagada,cancelada,entregada'
            ]);

            $nuevoE = $data['estado'];

            $estadoActual = $pedido->estado;

            $transicionesValidas = [
                'pendiente'=>['pagada','cancelada'],
                'pagada'=>['entregada'],
                'entregada'=>[],
                'cancelada'=>[],
            ];

            if(!in_array($nuevoE,$transicionesValidas[$estadoActual])){
                return response()->json([
                    'message'=>"No se puede cambiar de $estadoActual a $nuevoE"
                ],400);
            }

            $pedido->estado = $nuevoE;

            if($nuevoE === 'entregada'){
                $pedido->fecha_despacho = now();
            }

            $pedido->update();

            return response()->json([
                'message'=>"El pedido $pedido->correlativo ha sido actualizado a $nuevoE",
                'pedido'=>$pedido->load('detalles.producto', 'pagos')
            ]);

        } catch(\Exception $e){

            return response()->json([
                'message'=>'Error al actualizar el estado del pedido',
                'error'=>$e->getMessage()
            ],500);

        }

    }

    private function generateCorrelativo()
    {

        $year = now()->format('Y');
        $month = now()->format('m');

        // La columna en la tabla es fecha_pedido (no "fecha")
        $ultimo = Pedido::whereYear('fecha_pedido', $year)
                        ->whereMonth('fecha_pedido', $month)
                        ->lockForUpdate()
                        ->count();

        $numero = str_pad($ultimo + 1, 4, '0', STR_PAD_LEFT);

        return $year.$month.$numero;

    }
}


<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pedido;
use Barryvdh\DomPDF\Facade\Pdf;

class ReporteController extends Controller
{
    //Metodo para obtener las ordenes en un periodo de fechas
    public function getRange(Request $request){
        $fechaInicio = $request->fecha_inicio;
        $fechaFin = $request->fecha_fin;
        $estado = $request->estado;

        // consultas de ordenes por filtracion de rango de fechas
        $query = Pedido::with(['user', 'detalles.producto'])
        ->whereBetween('fecha_pedido', [$fechaInicio, $fechaFin]);

        //filtracion por estado
        if($estado != 'TODAS'){
            $query->where('estado', $estado);
        }

        $pedidos = $query->orderBy('fecha_pedido', 'desc')-> get();
        $totalVentas = $pedidos->sum('total');
        $totalPedidos = $pedidos->count();

        //mostrar el pdf

        $pdf = Pdf::loadView('reportes.pedidos', [
            'pedidos'=> $pedidos,
            'fechaInicio'=> $fechaInicio,
            'fechaFin'=> $fechaFin,
            'estado'=> $estado,
            'totalVentas'=> $totalVentas,
            'totalPedidos'=> $totalPedidos
        ]);

        $pdf->setPaper('A4', 'portrait');
        $pdf->getDomPDF()->set_option("enable_php", true);
        return $pdf->stream('reportes_pedidos.pdf');
    }
}

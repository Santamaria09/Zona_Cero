<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Pedido;

class DashboardController extends Controller
{
    //función para obtener totales
    public function getControl(){

    $ventasTotales = Pedido::where('estado',['pagada','entregada'])->sum('total');
        //obtener las ventas de la fecha
        $ventasHoy = Pedido::whereIn('estado',['pagada', 'entregada'])
        ->whereDate('fecha', Carbon::today())
        ->sum('total');

        $pedidosPagadas = Pedido::where('estado','pagada')->count();
        $pedidosPendientes = Pedido::where('estado','pendiente')->count();
        $pedidosCanceladas = Pedido::where('estado','cancelada')->count();
        return response()->json([
            'ventas_totales' => $ventasTotales,
            'ventas_hoy' => $ventasHoy,
            'pedidos_pagadas' =>$pedidosPagadas,
            'pedidos_pendientes' => $pedidosPendientes,
            'pedidos_canceladas' =>$pedidosCanceladas,
        ]);
    }

    //función para obtener las ventas por mes
    public function ventasPorMes(){
        $anioActual = now()->year;
        $ventas = Pedido::select(
            DB::raw("MONTH(fecha) as numero_mes"),
            DB::raw("MONTHNAME(fecha) as mes"),
            DB::raw("SUM(total) as total")
        )
        ->whereIn('estado',['pagada','entregada'])
        ->whereYear('fecha', $anioActual)
        ->groupBy(DB::raw("MONTH(fecha)"), DB::raw("MONTHNAME(fecha)"))
        ->orderBy("mes")
        ->get();
        return response()->json($ventas);
    }

    //funcion para obtener las ventas por año
    public function ventasPorAnio()
    {

        $ventas = Pedido::select(
                DB::raw("YEAR(fecha) as anio"),
                DB::raw("SUM(total) as total")
            )
            ->whereIn('estado',['pagada', 'entregada'])
            ->groupBy(DB::raw("YEAR(fecha)"))
            ->orderBy("anio")
            ->get();

        return response()->json($ventas);
    }

    //Top 5 de productos mas vendidos
    public function topProductos()
    {

        $productos = DB::table('detalle_pedidos')
            ->join('productos','detalle_pedidos.producto_id','=','productos.id')
            ->select(
                'productos.nombre',
                DB::raw("SUM(detalle_pedidos.cantidad) as vendidos")
            )
            ->groupBy('productos.nombre')
            ->orderByDesc('vendidos')
            ->limit(5)
            ->get();

        return response()->json($productos);
    }


}

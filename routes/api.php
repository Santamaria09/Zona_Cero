<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MarcaController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\UserController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('auth')->group(function(){

    // Rutas públicas de autenticación
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);

    Route::middleware('auth:api')->group(function(){
        Route::get('me',[AuthController::class, 'me']);
        Route::post('logout',[AuthController::class, 'logout']);
        Route::post('refresh',[AuthController::class, 'refresh']);
    });
});

Route::prefix('admin')->middleware(['auth:api', 'role:ADMIN'])->group(function(){
    Route::get('/dashboard/control', [DashboardController::class, 'getControl']);
    Route::get('/dashboard/ventas-mes', [DashboardController::class, 'ventasPorMes']);
    Route::get('/dashboard/ventas-anio',[DashboardController::class,'ventasPorAnio']);
    Route::get('/dashboard/top-productos',[DashboardController::class,'topProductos']);
    Route::patch('productos/{id}/toggle-activo',[ProductoController::class,'toggleActivo']);

    Route::get('users', [UserController::class, 'index']);
    Route::get('users/{id}', [UserController::class, 'show']);
    Route::put('users/{id}', [UserController::class, 'update']);

});

    Route::middleware(['auth:api', 'role:ADMIN|VENDEDOR'])->group(function () {
        Route::apiResource('marcas',MarcaController::class);
        Route::apiResource('categorias',CategoriaController::class);
        Route::apiResource('productos', ProductoController::class);
        Route::apiResource('pedidos', PedidoController::class);
        Route::put('pedidos/estado/{id}', [PedidoController::class, 'gestionarEstado']);
    });

Route::middleware(['auth:api', 'role:CLIENTE'])->group(function () {
    Route::post('pagos', [PagoController::class, 'store']);
    Route::post('pedidos', [PedidoController::class, 'store']);
    Route::get('pedidos', [PedidoController::class, 'index']);
    Route::get('pedidos/{id}', [PedidoController::class, 'show']);

    // Rutas aliases en inglés para pedidos
    Route::post('orders', [PedidoController::class, 'store']);
    Route::get('orders', [PedidoController::class, 'index']);
    Route::get('orders/{id}', [PedidoController::class, 'show']);
});

Route::get('productos', [ProductoController::class, 'index']);
Route::get('productos/{id}', [ProductoController::class, 'show']);

// Rutas públicas para categorías
Route::get('categorias', [CategoriaController::class, 'index']);
Route::get('categorias/{id}', [CategoriaController::class, 'show']);

// Rutas públicas para marcas
Route::get('marcas', [MarcaController::class, 'index']);
Route::get('marcas/{id}', [MarcaController::class, 'show']);









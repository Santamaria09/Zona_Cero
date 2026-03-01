<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
     protected $fillable = [
        'correlativo',
        'fecha_pedido',
        'fecha_entrega',
        'subtotal',
        'impuesto',
        'total',
        'estado',
        'user_id'
    ];

    protected $casts = [
        'fecha_pedido' => 'date',
        'fecha_entrega' => 'date',
        'subtotal' => 'decimal:2',
        'impuesto' => 'decimal:2',
        'total' => 'decimal:2'
    ];

    public function detalles()
    {
        return $this->hasMany(DetallePedido::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class);
    }
}

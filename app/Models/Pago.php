<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $fillable = [
        'monto',
        'estado',
        'respuesta_pasarela',
        'metodo_pago_id',
        'pedido_id'
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'respuesta_pasarela' => 'array'
    ];

    public function metodoPago()
    {
        return $this->belongsTo(MetodoPago::class);
    }

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }
}

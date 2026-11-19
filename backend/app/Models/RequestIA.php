<?php

namespace App\Models;

use App\Models\User;
use App\Models\Clients\Client;
use App\Models\Sales\InvoiceItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestIA extends Model
{
    protected $table = 'request_ia';

    protected $fillable = [
        'usuario_id',
        'cliente_id',
        'detalle_venta_id',
        'estado',
        'solicitud',
        'respuesta',
        'modelo_ia',
        'mensaje_error',
        'tipo_solicitud',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'cliente_id');
    }

    public function detalleVenta(): BelongsTo
    {
        return $this->belongsTo(
            InvoiceItem::class,
            'detalle_venta_id'
        );
    }
}

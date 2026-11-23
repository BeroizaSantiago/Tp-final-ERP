<?php

namespace App\Models\IA;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Contenido enriquecido y aprobado para una ficha del catálogo.
 *
 * No reemplaza los datos maestros: guarda borradores generados por la IA o
 * textos cargados a mano que, una vez aprobados, se usan como contexto extra
 * en las próximas consultas.
 */
class AiEnrichment extends Model
{
    protected $table = 'ai_enrichments';

    protected $fillable = [
        'enrichable_type',
        'enrichable_id',
        'kind',
        'content',
        'source',
        'model',
        'status',
        'created_by',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function enrichable(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

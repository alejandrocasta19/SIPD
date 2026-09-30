<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoordinadoraNotificacion extends Model
{
    public const TIPO_VEREDICTO = 'veredicto';

    protected $table = 'coordinadora_notificaciones';

    protected $fillable = [
        'tipo',
        'proceso_id',
        'titulo',
        'cuerpo',
        'leida_at',
    ];

    protected $casts = [
        'leida_at' => 'datetime',
    ];

    public function proceso()
    {
        return $this->belongsTo(ProcesoDisciplinario::class, 'proceso_id');
    }

    public function estaLeida(): bool
    {
        return $this->leida_at !== null;
    }

    public static function avisarVeredicto(ProcesoDisciplinario $proceso, User $quienEnvia): self
    {
        $codigo = 'PRO-' . str_pad((string) $proceso->id, 3, '0', STR_PAD_LEFT);
        $cuerpo = $codigo . ' · ' . $proceso->nombre . ' fue enviado por ' . $quienEnvia->name . '.';

        Aviso::aCoordinadoras([
            'remitente_id' => $quienEnvia->id,
            'tipo' => Aviso::TIPO_VEREDICTO,
            'titulo' => 'Caso pendiente de veredicto',
            'cuerpo' => $cuerpo,
            'proceso_id' => $proceso->id,
        ]);

        return self::create([
            'tipo' => self::TIPO_VEREDICTO,
            'proceso_id' => $proceso->id,
            'titulo' => 'Caso pendiente de veredicto',
            'cuerpo' => $cuerpo,
        ]);
    }

    public static function marcarVeredictosLeidos(): void
    {
        self::query()
            ->where('tipo', self::TIPO_VEREDICTO)
            ->whereNull('leida_at')
            ->update(['leida_at' => now()]);
    }
}

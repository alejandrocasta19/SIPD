<?php

namespace App\Support;

use App\Models\Aviso;
use App\Models\ProcesoDisciplinario;
use App\Models\User;

class WorkflowNotifications
{
    public static function caseEvent(
        ProcesoDisciplinario $proceso,
        User $actor,
        string $accion,
        ?string $detalle = null,
        ?string $tipoDocumento = null,
        bool $includeProcessLink = true
    ): void {
        $codigo = 'PRO-' . str_pad((string) $proceso->id, 3, '0', STR_PAD_LEFT);
        $data = [
            'remitente_id' => $actor->id,
            'tipo' => Aviso::TIPO_FLUJO,
            'titulo' => $accion . ' · ' . $codigo,
            'cuerpo' => $actor->name . ' ' . mb_strtolower($accion) . ' en el expediente ' . $codigo
                . ($detalle ? ': ' . $detalle : '.'),
            'proceso_id' => $includeProcessLink ? $proceso->id : null,
            'tipo_documento' => $tipoDocumento,
        ];

        if ($actor->esCoordinadora()) {
            $responsable = $proceso->user;
            if ($responsable && $responsable->esEquipo() && (int) $responsable->id !== (int) $actor->id) {
                Aviso::enviar($responsable, $data);
            }

            return;
        }

        Aviso::aCoordinadoras($data, $actor->id);
    }

    public static function assignment(ProcesoDisciplinario $proceso, User $responsable, User $coordinadora): void
    {
        $codigo = 'PRO-' . str_pad((string) $proceso->id, 3, '0', STR_PAD_LEFT);

        Aviso::enviar($responsable, [
            'remitente_id' => $coordinadora->id,
            'tipo' => Aviso::TIPO_ASIGNACION,
            'titulo' => 'Nuevo expediente asignado · ' . $codigo,
            'cuerpo' => $coordinadora->name . ' te asignó el expediente ' . $codigo
                . ' · ' . $proceso->nombre . '. Confirma la recepción desde esta notificación.',
            'proceso_id' => $proceso->id,
        ]);
    }

    public static function assignmentReceived(ProcesoDisciplinario $proceso, User $responsable): void
    {
        $codigo = 'PRO-' . str_pad((string) $proceso->id, 3, '0', STR_PAD_LEFT);

        Aviso::aCoordinadoras([
            'remitente_id' => $responsable->id,
            'tipo' => Aviso::TIPO_RECEPCION,
            'titulo' => 'Recepción confirmada · ' . $codigo,
            'cuerpo' => $responsable->name . ' confirmó que recibió el expediente ' . $codigo . '.',
            'proceso_id' => $proceso->id,
        ], $responsable->id);
    }
}

<?php

namespace App\Support;

use App\Models\AlertaSilencio;
use App\Models\ProcesoDisciplinario;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class AlertasSistema
{
    public const PLAZOS = 'plazos_vencidos';
    public const SIN_RH = 'sin_rh';
    public const VEREDICTOS = 'veredictos';
    public const DESCARGOS = 'descargos';

    public static function tipos(): array
    {
        return [self::PLAZOS, self::SIN_RH, self::VEREDICTOS, self::DESCARGOS];
    }

    public static function visibles(User $user): array
    {
        $items = [];
        foreach (self::catalogo($user) as $item) {
            if ($item['count'] > 0) {
                $items[] = $item;
            }
        }

        return $items;
    }

    public static function silenciar(User $user, string $tipo): int
    {
        abort_unless(in_array($tipo, self::tipos(), true), 404);

        $ids = self::idsVisibles($user, $tipo);
        if ($ids === []) {
            return 0;
        }

        $now = now();
        $rows = array_map(static function ($procesoId) use ($user, $tipo, $now) {
            return [
                'user_id' => $user->id,
                'tipo' => $tipo,
                'proceso_id' => $procesoId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $ids);

        AlertaSilencio::query()->upsert($rows, ['user_id', 'tipo', 'proceso_id'], ['updated_at']);

        return count($ids);
    }

    public static function silenciarTodas(User $user): int
    {
        $total = 0;
        foreach (self::tipos() as $tipo) {
            $total += self::silenciar($user, $tipo);
        }

        return $total;
    }

    private static function catalogo(User $user): array
    {
        return [
            self::item($user, self::PLAZOS, 'Plazos vencidos', 'far fa-clock', 'warn',
                fn (int $n) => $n === 1
                    ? '1 proceso superó el término de 5 días'
                    : "{$n} procesos superaron el término de 5 días",
                Route::has('abogado.plazos') ? route('abogado.plazos', ['estado' => 'vencido']) : '#'
            ),
            self::item($user, self::SIN_RH, 'Sin RH asignado', 'fas fa-user-slash', 'info',
                fn (int $n) => $n === 1
                    ? '1 proceso sin responsable'
                    : "{$n} procesos sin responsable",
                Route::has('abogado.consultarproceso') ? route('abogado.consultarproceso') : '#'
            ),
            self::item($user, self::VEREDICTOS, 'Pendientes de veredicto', 'fas fa-gavel', 'info',
                fn (int $n) => $n === 1
                    ? '1 expediente en proceso'
                    : "{$n} expedientes en proceso",
                Route::has('coordinadora.veredictos') ? route('coordinadora.veredictos') : '#'
            ),
            self::item($user, self::DESCARGOS, 'Descargos pendientes', 'far fa-comment-dots', 'ok',
                fn (int $n) => $n === 1
                    ? '1 expediente espera respuesta'
                    : "{$n} expedientes esperan respuesta",
                Route::has('abogado.consultarproceso') ? route('abogado.consultarproceso') : '#'
            ),
        ];
    }

    private static function item(User $user, string $tipo, string $titulo, string $icono, string $tono, callable $detalle, string $href): array
    {
        $ids = self::idsVisibles($user, $tipo);
        $count = count($ids);

        return [
            'tipo' => $tipo,
            'titulo' => $titulo,
            'detalle' => $detalle($count),
            'icono' => $icono,
            'tono' => $tono,
            'href' => $href,
            'count' => $count,
            'ids' => $ids,
        ];
    }

    private static function idsVisibles(User $user, string $tipo): array
    {
        if (!$user->esCoordinadora() && in_array($tipo, [self::SIN_RH, self::VEREDICTOS], true)) {
            return [];
        }

        $ids = self::consulta($user, $tipo)->pluck('id')->map(fn ($id) => (int) $id)->all();
        if ($ids === []) {
            return [];
        }

        $ocultos = AlertaSilencio::query()
            ->where('user_id', $user->id)
            ->where('tipo', $tipo)
            ->whereIn('proceso_id', $ids)
            ->pluck('proceso_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_values(array_diff($ids, $ocultos));
    }

    private static function consulta(User $user, string $tipo)
    {
        $query = ProcesoDisciplinario::query();
        if (!$user->esCoordinadora()) {
            $query->where('user_id', $user->id);
        }

        if ($tipo === self::PLAZOS) {
            return $query->whereIn('estado', ['Pendiente', 'En Proceso'])
                ->whereDate('created_at', '<=', now()->subDays(5)->toDateString());
        }

        if ($tipo === self::SIN_RH) {
            return $query->whereNull('user_id');
        }

        if ($tipo === self::VEREDICTOS) {
            return $query->where('estado', 'En Proceso');
        }

        return $query->whereIn('estado', ['Pendiente', 'En Proceso'])
            ->where(function ($inner) {
                $inner->whereNull('descargos')->orWhere('descargos', '');
            });
    }
}

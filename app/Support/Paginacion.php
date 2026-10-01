<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class Paginacion
{
    public const OPCIONES = [5, 10, 15];
    public const DEFAULT = 10;
    public const MAX = 100;

    public static function porPagina(?Request $request = null, int $default = self::DEFAULT): int
    {
        $request = $request ?: request();
        $valor = (int) $request->input('per_page', $default);

        if ($valor < 1) {
            return $default;
        }

        return min($valor, self::MAX);
    }

    public static function esPreset(int $cantidad): bool
    {
        return in_array($cantidad, self::OPCIONES, true);
    }

    public static function deQuery($query, int $default = self::DEFAULT): LengthAwarePaginator
    {
        return $query->paginate(self::porPagina(null, $default))->withQueryString();
    }

    public static function deColeccion($items, int $default = self::DEFAULT): LengthAwarePaginator
    {
        $items = $items instanceof Collection ? $items : collect($items);
        $perPage = self::porPagina(null, $default);
        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => request()->query(),
            ]
        );
    }
}

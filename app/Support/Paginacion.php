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

    public static function porPagina(?Request $request = null, int $default = self::DEFAULT, string $pageName = 'page'): int
    {
        $request = $request ?: request();
        $paramName = $pageName === 'page' ? 'per_page' : "per_page_{$pageName}";
        $valor = (int) $request->input($paramName, $default);

        if ($valor < 1) {
            return $default;
        }

        return min($valor, self::MAX);
    }

    public static function esPreset(int $cantidad): bool
    {
        return in_array($cantidad, self::OPCIONES, true);
    }

    public static function deQuery($query, int $default = self::DEFAULT, string $pageName = 'page'): LengthAwarePaginator
    {
        return $query->paginate(self::porPagina(null, $default, $pageName), ['*'], $pageName)->withQueryString();
    }

    public static function deColeccion($items, int $default = self::DEFAULT, string $pageName = 'page'): LengthAwarePaginator
    {
        $items = $items instanceof Collection ? $items : collect($items);
        $perPage = self::porPagina(null, $default, $pageName);
        $page = LengthAwarePaginator::resolveCurrentPage($pageName);

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => request()->query(),
                'pageName' => $pageName,
            ]
        );
    }
}

<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class CaseFilePath
{
    public static function resolve(string $path, array $allowedPrefixes, string $disk = 'local'): ?string
    {
        if (
            $path === ''
            || strpos($path, "\0") !== false
            || strpos($path, '\\') !== false
            || $path[0] === '/'
            || preg_match('/^[A-Za-z]:/', $path)
        ) {
            abort(404);
        }

        $segments = explode('/', $path);
        foreach ($segments as $segment) {
            if (
                $segment === ''
                || $segment === '.'
                || $segment === '..'
                || !preg_match('/^[A-Za-z0-9._-]+$/D', $segment)
            ) {
                abort(404);
            }
        }

        $allowed = false;
        foreach ($allowedPrefixes as $prefix) {
            if (strpos($path, rtrim($prefix, '/') . '/') === 0) {
                $allowed = true;
                break;
            }
        }
        abort_unless($allowed, 404);

        $storage = Storage::disk($disk);
        $root = realpath($storage->path(''));
        $resolved = realpath($storage->path($path));
        if ($root === false || $resolved === false || !is_file($resolved)) {
            return null;
        }

        $rootPrefix = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $insideRoot = DIRECTORY_SEPARATOR === '\\'
            ? strncasecmp($resolved, $rootPrefix, strlen($rootPrefix)) === 0
            : strncmp($resolved, $rootPrefix, strlen($rootPrefix)) === 0;
        abort_unless($insideRoot, 404);

        return $resolved;
    }
}

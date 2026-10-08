<?php

namespace App\Console\Commands;

use App\Models\ProcesoDisciplinario;
use Illuminate\Console\Command;

class ArchiveClosedCases extends Command
{
    protected $signature = 'sipd:archive-closed-cases';

    protected $description = 'Archiva los procesos cerrados y conserva sus datos para Reportes';

    public function handle(): int
    {
        $archived = ProcesoDisciplinario::query()
            ->whereIn('estado', ['Sancionado', 'Archivado'])
            ->delete();

        $this->info("Casos cerrados archivados: {$archived}");

        return self::SUCCESS;
    }
}

<?php

namespace Tests\Feature;

use App\Models\ProcesoDisciplinario;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReporteExportTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function informe_global_incluye_identidad_y_apartados_ampliados()
    {
        $user = User::factory()->create([
            'role' => 'abogado',
            'name' => 'Kelly RH',
        ]);
        ProcesoDisciplinario::factory()->create([
            'user_id' => $user->id,
            'nombre' => 'Juan Jose',
            'estado' => 'Pendiente',
            'modalidad' => 'Premium',
            'ruta' => 'Neiva - Pitalito',
            'tipo_falta' => 'Atraso de ruta',
        ]);

        $this->actingAs($user);

        $data = app(ReportService::class)->statistics(
            ProcesoDisciplinario::query()->where('user_id', $user->id),
            null,
            null
        );

        $html = view('reports.global-pdf', ['data' => $data])->render();

        $this->assertStringContainsString('COOTRANSHUILA', $html);
        $this->assertStringContainsString('Informe de gestión disciplinaria', $html);
        $this->assertStringContainsString('Uso interno', $html);
        $this->assertStringContainsString('Cargo del trabajador', $html);
        $this->assertStringContainsString('Rutas', $html);
        $this->assertStringContainsString('Descargos', $html);
        $this->assertStringContainsString('Premium', $html);
        $this->assertArrayHasKey('by_ruta', $data);
        $this->assertArrayHasKey('kpis', $data);
        $this->assertSame(1, $data['kpis']['pendientes']);
    }

    /** @test */
    public function relacion_de_casos_incluye_radicado_y_ruta()
    {
        $user = User::factory()->create(['role' => 'abogado', 'name' => 'Analista RH']);
        $caso = ProcesoDisciplinario::factory()->create([
            'user_id' => $user->id,
            'nombre' => 'Conductor Norte',
            'ruta' => 'Neiva - Garzón',
            'estado' => 'En Proceso',
        ]);
        $caso->load('user');
        $caso->anexos_count = 0;

        $this->actingAs($user);

        $html = view('reports.cases-pdf', [
            'cases' => collect([$caso]),
            'meta' => app(ReportService::class)->reportMeta('Relación de expedientes seleccionados'),
        ])->render();

        $this->assertStringContainsString('COOTRANSHUILA', $html);
        $this->assertStringContainsString($caso->numeroRadicado(), $html);
        $this->assertStringContainsString('Neiva - Garzón', $html);
        $this->assertStringContainsString('Conductor Norte', $html);
    }

    /** @test */
    public function se_puede_descargar_el_informe_global_en_pdf_word_y_excel()
    {
        $user = User::factory()->create(['role' => 'abogado']);
        ProcesoDisciplinario::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post(route('abogado.reportes.global', 'pdf'))
            ->assertOk();

        $this->actingAs($user)
            ->post(route('abogado.reportes.global', 'word'))
            ->assertOk();

        $this->actingAs($user)
            ->post(route('abogado.reportes.global', 'excel'))
            ->assertOk();
    }

    /** @test */
    public function excel_del_informe_incluye_lineas_de_separacion()
    {
        $user = User::factory()->create(['role' => 'abogado']);
        ProcesoDisciplinario::factory()->create([
            'user_id' => $user->id,
            'estado' => 'Pendiente',
        ]);
        $this->actingAs($user);

        $service = app(ReportService::class);
        $data = $service->statistics(
            ProcesoDisciplinario::query()->where('user_id', $user->id),
            null,
            null
        );

        $ref = new \ReflectionClass($service);
        $build = $ref->getMethod('buildExcelFromLayout');
        $build->setAccessible(true);
        $layout = $ref->getMethod('globalLayout');
        $layout->setAccessible(true);

        $spreadsheet = $build->invoke($service, $layout->invoke($service, $data), false);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertNotSame(
            \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE,
            $sheet->getStyle('A9')->getBorders()->getLeft()->getBorderStyle()
        );

        $estadoRow = null;
        for ($r = 10; $r <= 40; $r++) {
            if ((string) $sheet->getCell('A' . $r)->getValue() === 'Estado') {
                $estadoRow = $r;
                break;
            }
        }
        $this->assertNotNull($estadoRow);
        $this->assertNotSame(
            \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE,
            $sheet->getStyle('B' . ($estadoRow + 1))->getBorders()->getRight()->getBorderStyle()
        );
        $this->assertNotSame(
            \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE,
            $sheet->getStyle('C' . ($estadoRow + 1))->getBorders()->getLeft()->getBorderStyle()
        );
    }
}

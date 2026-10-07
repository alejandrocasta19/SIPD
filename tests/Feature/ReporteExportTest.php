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
            'role' => 'equipo',
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
        $this->assertStringContainsString('Modalidad y cargo', $html);
        $this->assertStringContainsString('Rutas', $html);
        $this->assertStringContainsString('Descargos', $html);
        $this->assertStringContainsString('Premium', $html);
        $this->assertArrayHasKey('by_ruta', $data);
        $this->assertArrayHasKey('kpis', $data);
        $this->assertSame(1, $data['kpis']['pendientes']);
    }

    /** @test */
    public function estadisticas_diarias_completan_los_dias_sin_registros_y_reemplazan_la_grafica_de_rutas()
    {
        $user = User::factory()->create(['role' => 'equipo']);
        ProcesoDisciplinario::factory()->create([
            'user_id' => $user->id,
            'created_at' => '2026-01-01 10:00:00',
            'updated_at' => '2026-01-01 10:00:00',
        ]);
        ProcesoDisciplinario::factory()->create([
            'user_id' => $user->id,
            'created_at' => '2026-01-03 10:00:00',
            'updated_at' => '2026-01-03 10:00:00',
        ]);

        $service = app(ReportService::class);
        $data = $service->statistics(
            ProcesoDisciplinario::query()->where('user_id', $user->id),
            '2026-01-01',
            '2026-01-03'
        );

        $this->assertSame([
            ['period' => '2026-01-01', 'total' => 1],
            ['period' => '2026-01-02', 'total' => 0],
            ['period' => '2026-01-03', 'total' => 1],
        ], $data['daily']);

        $chartSpecsMethod = (new \ReflectionClass($service))->getMethod('reportChartSpecs');
        $chartSpecsMethod->setAccessible(true);
        $chartTitles = array_column($chartSpecsMethod->invoke($service, $data), 'title');

        $this->assertSame(
            ['Casos diarios', 'Casos semanales', 'Casos por mes'],
            array_slice($chartTitles, 0, 3)
        );
        $this->assertNotContains('Rutas frecuentes', $chartTitles);
    }

    /** @test */
    public function coordinadora_recibe_desglose_de_todas_las_graficas_por_integrante_y_el_equipo_solo_sus_datos()
    {
        $coordinadora = User::factory()->create(['role' => 'admin']);
        $equipoA = User::factory()->create(['role' => 'equipo', 'name' => 'Ana RH']);
        $equipoB = User::factory()->create(['role' => 'equipo', 'name' => 'Luis RH']);

        ProcesoDisciplinario::factory()->create([
            'user_id' => $equipoA->id,
            'estado' => 'Pendiente',
            'modalidad' => 'Premium',
            'tipo_falta' => 'Atraso',
            'created_at' => '2026-01-01 10:00:00',
            'updated_at' => '2026-01-01 10:00:00',
        ]);
        ProcesoDisciplinario::factory()->create([
            'user_id' => $equipoB->id,
            'estado' => 'Sancionado',
            'modalidad' => 'Basico',
            'tipo_falta' => 'Inasistencia',
            'created_at' => '2026-01-02 10:00:00',
            'updated_at' => '2026-01-02 10:00:00',
        ]);

        $coordinatorResponse = $this->actingAs($coordinadora)
            ->getJson(route('abogado.reportes.datos', [
                'desde' => '2026-01-01',
                'hasta' => '2026-01-02',
            ]))
            ->assertOk()
            ->assertJsonCount(2, 'by_user');

        $owners = collect($coordinatorResponse->json('by_user'))->keyBy('name');
        $this->assertSame(1, $owners['Ana RH']['daily'][0]['total']);
        $this->assertSame(1, $owners['Luis RH']['daily'][1]['total']);
        $this->assertSame(1, $owners['Ana RH']['pending_faults'][0]['total']);
        $this->assertSame(1, $owners['Luis RH']['by_modalidad'][0]['total']);
        $this->assertSame(1, $owners['Luis RH']['states']['Sancionado']);

        $teamResponse = $this->actingAs($equipoA)
            ->getJson(route('abogado.reportes.datos', [
                'desde' => '2026-01-01',
                'hasta' => '2026-01-02',
            ]))
            ->assertOk()
            ->assertJsonPath('total', 1);
        $this->assertArrayNotHasKey('by_user', $teamResponse->json());
    }

    /** @test */
    public function relacion_de_casos_incluye_radicado_y_ruta()
    {
        $user = User::factory()->create(['role' => 'equipo', 'name' => 'Analista RH']);
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
        $user = User::factory()->create(['role' => 'equipo']);
        $user->otorgarPermiso('exportar_reportes', null);
        ProcesoDisciplinario::factory()->create(['user_id' => $user->id]);

        $pdf = $this->actingAs($user)->post(route('abogado.reportes.global', 'pdf'));
        $pdf->assertOk();
        $pdfContents = $pdf->getContent();
        $this->assertIsString($pdfContents);
        $this->assertStringContainsString('/Subtype /Image', $pdfContents);

        $word = $this->actingAs($user)->post(route('abogado.reportes.global', 'word'));
        $word->assertOk();
        $this->assertDownloadedArchiveContainsCharts($word, 'word/media/');

        $excel = $this->actingAs($user)->post(route('abogado.reportes.global', 'excel'));
        $excel->assertOk();
        $this->assertDownloadedArchiveContainsCharts($excel, 'xl/media/');
    }

    /** @test */
    public function excel_del_informe_incluye_lineas_de_separacion()
    {
        $user = User::factory()->create(['role' => 'equipo']);
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

    private function assertDownloadedArchiveContainsCharts($response, string $mediaDirectory): void
    {
        $path = $response->baseResponse->getFile()->getPathname();
        $archive = new \ZipArchive();
        $opened = $archive->open($path);
        $mediaFiles = [];
        $drawingDetails = [];

        try {
            $this->assertSame(true, $opened);
            for ($index = 0; $index < $archive->numFiles; $index++) {
                $name = $archive->getNameIndex($index);
                if (strpos($name, $mediaDirectory) === 0 && preg_match('/\.png$/i', $name)) {
                    $mediaFiles[] = $name;
                }
            }
            if ($mediaDirectory === 'xl/media/') {
                for ($index = 0; $index < $archive->numFiles; $index++) {
                    $name = $archive->getNameIndex($index);
                    if (preg_match('#^xl/drawings/drawing\d+\.xml$#', $name)) {
                        $drawingDetails[$name] = substr_count($archive->getFromName($name), '<xdr:pic>');
                    }
                }
                $this->assertContains(6, array_values($drawingDetails));
            }
        } finally {
            if ($opened === true) {
                $archive->close();
            }
            if (is_file($path)) {
                unlink($path);
            }
        }

        $this->assertGreaterThanOrEqual(6, count($mediaFiles));
        $this->assertLessThanOrEqual(7, count($mediaFiles));
    }
}

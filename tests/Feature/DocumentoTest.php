<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\User;
use App\Models\ProcesoDisciplinario;
use App\Models\CasoEvidencia;
use App\Models\CasoDocumentoEstado;

class DocumentoTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ────────────────────────────────────────────────────

    private function makeUser(string $role = 'abogado'): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function makeCaso(User $user, array $attrs = []): ProcesoDisciplinario
    {
        return ProcesoDisciplinario::factory()->create(array_merge([
            'user_id'    => $user->id,
            'nombre'     => 'Test Trabajador',
            'estado'     => 'Pendiente',
            'tipo_proceso' => 'disciplinario',
        ], $attrs));
    }

    private function filledBlocks(string $tipo = 'disciplinario'): array
    {
        $counts = [
            'disciplinario' => 8,
            'comprobacion' => 25,
            'acta' => 10,
            'sancion' => 172,
            'llamado' => 6,
            'terminacion' => 111,
            'archivo' => 9,
        ];
        $n      = $counts[$tipo] ?? 8;
        return array_fill(0, $n, 'Texto de prueba para este bloque amarillo.');
    }

    // ─────────────────────────────────────────────────────────────────
    // SEGURIDAD: ABOGADO SOLO ACCEDE A SUS CASOS
    // ─────────────────────────────────────────────────────────────────

    /** @test */
    public function abogado_no_puede_ver_documentos_de_caso_ajeno()
    {
        $abogado = $this->makeUser('abogado');
        $otro    = $this->makeUser('abogado');
        $caso    = $this->makeCaso($otro);

        $this->actingAs($abogado)
            ->get(route('documentos.index', $caso->id))
            ->assertStatus(404);
    }

    /** @test */
    public function coordinadora_puede_ver_documentos_de_cualquier_caso()
    {
        $coord = $this->makeUser('coordinadora');
        $abog  = $this->makeUser('abogado');
        $caso  = $this->makeCaso($abog);

        $this->actingAs($coord)
            ->get(route('documentos.index', $caso->id))
            ->assertStatus(200);
    }

    /** @test */
    public function usuario_no_autenticado_redirige_a_login()
    {
        $abog = $this->makeUser('abogado');
        $caso = $this->makeCaso($abog);

        $this->get(route('documentos.index', $caso->id))
            ->assertRedirect(route('login'));
    }

    /** @test */
    public function abogado_no_puede_acceder_a_editar_caso_ajeno_por_url_directa()
    {
        $abog1 = $this->makeUser('abogado');
        $abog2 = $this->makeUser('abogado');
        $caso  = $this->makeCaso($abog2);

        $this->actingAs($abog1)
            ->get(route('documentos.edit', [$caso->id, 'disciplinario']))
            ->assertStatus(404);
    }

    /** @test */
    public function abogado_no_puede_descargar_documento_de_caso_ajeno()
    {
        $abog1 = $this->makeUser('abogado');
        $abog2 = $this->makeUser('abogado');
        $caso  = $this->makeCaso($abog2);

        $this->actingAs($abog1)
            ->get(route('documentos.download', [$caso->id, 'disciplinario']))
            ->assertStatus(404);
    }

    // ─────────────────────────────────────────────────────────────────
    // MÓDULO DOCUMENTOS — ÍNDICE
    // ─────────────────────────────────────────────────────────────────

    /** @test */
    public function index_muestra_cuatro_espacios_documentales()
    {
        $user = $this->makeUser('abogado');
        $caso = $this->makeCaso($user);

        $this->actingAs($user)
            ->get(route('documentos.index', $caso->id))
            ->assertStatus(200)
            ->assertSee('1. Apertura')
            ->assertSee('2. Acta de cargos y descargos')
            ->assertSee('3. Sanción / llamado / terminación')
            ->assertSee('4. Decisión de archivo')
            ->assertSee('1.1 GA-FT-045 Apertura disciplinaria');
    }

    /** @test */
    public function se_puede_elegir_la_variante_de_apertura()
    {
        $user = $this->makeUser('abogado');
        $caso = $this->makeCaso($user);

        $this->actingAs($user)
            ->post(route('documentos.variante', $caso->id), ['tipo' => 'comprobacion'])
            ->assertRedirect(route('documentos.edit', [$caso->id, 'comprobacion']));

        $caso->refresh();
        $this->assertSame('comprobacion', $caso->varianteDelSlot('apertura'));
    }

    /** @test */
    public function se_puede_elegir_sancion_llamado_o_terminacion()
    {
        $user = $this->makeUser('abogado');
        $caso = $this->makeCaso($user);

        $this->actingAs($user)
            ->post(route('documentos.variante', $caso->id), ['tipo' => 'llamado'])
            ->assertRedirect(route('documentos.edit', [$caso->id, 'llamado']));

        $caso->refresh();
        $this->assertSame('llamado', $caso->varianteDelSlot('resolucion'));
    }

    /** @test */
    public function index_crea_estados_iniciales_al_visitar()
    {
        $user = $this->makeUser('abogado');
        $caso = $this->makeCaso($user);

        $this->actingAs($user)->get(route('documentos.index', $caso->id));

        $this->assertDatabaseHas('caso_documento_estados', [
            'caso_id'        => $caso->id,
            'tipo_documento' => 'disciplinario',
            'estado'         => 'no_iniciado',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // CAMPOS AMARILLOS — GUARDAR
    // ─────────────────────────────────────────────────────────────────

    /** @test */
    public function guardar_bloques_amarillos_persiste_datos()
    {
        $user = $this->makeUser('abogado');
        $caso = $this->makeCaso($user);

        $blocks = $this->filledBlocks('disciplinario');

        $this->actingAs($user)
            ->put(route('documentos.save', [$caso->id, 'disciplinario']), [
                'yellow_blocks' => $blocks,
            ])
            ->assertRedirect(route('documentos.edit', [$caso->id, 'disciplinario']));

        $caso->refresh();
        $this->assertSame(
            $blocks[0],
            $caso->datos_oficiales['yellow_blocks']['disciplinario'][0]
        );
    }

    /** @test */
    public function guardar_bloques_cambia_estado_a_completo_cuando_todos_llenos()
    {
        $user = $this->makeUser('abogado');
        $caso = $this->makeCaso($user);

        $this->actingAs($user)->put(
            route('documentos.save', [$caso->id, 'disciplinario']),
            ['yellow_blocks' => $this->filledBlocks('disciplinario')]
        );

        $this->assertDatabaseHas('caso_documento_estados', [
            'caso_id'        => $caso->id,
            'tipo_documento' => 'disciplinario',
            'estado'         => 'completo',
        ]);
    }

    /** @test */
    public function guardar_con_bloques_incompletos_pone_estado_en_diligenciamiento()
    {
        $user   = $this->makeUser('abogado');
        $caso   = $this->makeCaso($user);
        $blocks = $this->filledBlocks('disciplinario');
        $blocks[3] = ''; // dejar uno vacío

        $this->actingAs($user)->put(
            route('documentos.save', [$caso->id, 'disciplinario']),
            ['yellow_blocks' => $blocks]
        );

        $this->assertDatabaseHas('caso_documento_estados', [
            'caso_id'        => $caso->id,
            'tipo_documento' => 'disciplinario',
            'estado'         => 'en_diligenciamiento',
        ]);
    }

    /** @test */
    public function no_se_puede_guardar_sin_autenticacion()
    {
        $user = $this->makeUser('abogado');
        $caso = $this->makeCaso($user);

        $this->put(route('documentos.save', [$caso->id, 'disciplinario']), [
            'yellow_blocks' => $this->filledBlocks(),
        ])->assertRedirect(route('login'));
    }

    /** @test */
    public function guardar_comprobacion_funciona_con_25_bloques()
    {
        $user = $this->makeUser('abogado');
        $caso = $this->makeCaso($user, ['tipo_proceso' => 'comprobacion']);

        $this->actingAs($user)->put(
            route('documentos.save', [$caso->id, 'comprobacion']),
            ['yellow_blocks' => $this->filledBlocks('comprobacion')]
        )->assertRedirect();

        $caso->refresh();
        $this->assertCount(25, $caso->datos_oficiales['yellow_blocks']['comprobacion']);
    }

    /** @test */
    public function guardar_acta_funciona_con_10_bloques()
    {
        $user = $this->makeUser('abogado');
        $caso = $this->makeCaso($user);

        $this->actingAs($user)->put(
            route('documentos.save', [$caso->id, 'acta']),
            ['yellow_blocks' => $this->filledBlocks('acta')]
        )->assertRedirect();

        $caso->refresh();
        $this->assertCount(10, $caso->datos_oficiales['yellow_blocks']['acta']);
    }

    /** @test */
    public function guardar_documento_completo_descarga_solo_ese_tipo()
    {
        $user = $this->makeUser('abogado');
        $caso = $this->makeCaso($user);

        $this->actingAs($user)
            ->put(route('documentos.save', [$caso->id, 'disciplinario']), [
                'yellow_blocks_disciplinario' => $this->filledBlocks('disciplinario'),
                'formato' => 'docx',
            ])
            ->assertRedirect(route('documentos.edit', [$caso->id, 'disciplinario']))
            ->assertSessionHas('autodownload', 'disciplinario')
            ->assertSessionHas('autodownload_format', 'docx');
    }

    /** @test */
    public function guardar_comprobacion_no_prepara_descarga_de_otro_documento()
    {
        $user = $this->makeUser('abogado');
        $caso = $this->makeCaso($user);

        $this->actingAs($user)
            ->put(route('documentos.save', [$caso->id, 'comprobacion']), [
                'yellow_blocks_comprobacion' => $this->filledBlocks('comprobacion'),
                'formato' => 'pdf',
            ])
            ->assertSessionHas('autodownload', 'comprobacion')
            ->assertSessionHas('autodownload_format', 'pdf');
    }

    // ─────────────────────────────────────────────────────────────────
    // TIPO DE DOCUMENTO INVÁLIDO
    // ─────────────────────────────────────────────────────────────────

    /** @test */
    public function guardar_llamado_y_archivo_persisten_bloques()
    {
        $user = $this->makeUser('abogado');
        $caso = $this->makeCaso($user);

        $this->actingAs($user)->put(
            route('documentos.save', [$caso->id, 'llamado']),
            ['yellow_blocks' => $this->filledBlocks('llamado')]
        )->assertRedirect();

        $this->actingAs($user)->put(
            route('documentos.save', [$caso->id, 'archivo']),
            ['yellow_blocks' => $this->filledBlocks('archivo')]
        )->assertRedirect();

        $caso->refresh();
        $this->assertCount(6, $caso->datos_oficiales['yellow_blocks']['llamado']);
        $this->assertCount(9, $caso->datos_oficiales['yellow_blocks']['archivo']);
        $this->assertSame('archivo', $caso->varianteDelSlot('archivo'));
        $this->assertSame('llamado', $caso->varianteDelSlot('resolucion'));
    }

    /** @test */
    public function registro_muestra_cuatro_pestanias_de_formato()
    {
        $user = $this->makeUser('abogado');

        $this->actingAs($user)
            ->get(route('abogado.registro'))
            ->assertOk()
            ->assertSee('1. Apertura')
            ->assertSee('2. Acta de cargos y descargos')
            ->assertSee('3. Sanción / llamado / terminación')
            ->assertSee('4. Decisión de archivo')
            ->assertSee('Usar formato')
            ->assertSee('sipd_nuevo_proceso', false);
    }

    /** @test */
    public function registrar_proceso_marca_borrador_para_limpiar()
    {
        $user = $this->makeUser('abogado');

        $this->actingAs($user)
            ->post(route('abogado.registro.store'), [
                'tipo_proceso' => 'terminacion',
                'nombre' => 'Conductor Demo',
            ])
            ->assertRedirect()
            ->assertSessionHas('clear_nuevo_draft');
    }

    /** @test */
    public function hub_muestra_nombre_del_formato_en_lugar_de_generado_o_borrador()
    {
        $user = $this->makeUser('abogado');
        $caso = $this->makeCaso($user, [
            'tipo_proceso' => 'comprobacion',
            'datos_oficiales' => ['slots' => ['apertura' => 'comprobacion']],
        ]);
        $caso->estadoDocumento('comprobacion')->update(['estado' => 'en_diligenciamiento']);
        $caso->estadoDocumento('acta')->update(['estado' => 'generado']);

        $this->actingAs($user)
            ->get(route('documentos.hub'))
            ->assertOk()
            ->assertSee('Pendiente')
            ->assertSee('1.1 GA-FT-045 Apertura de comprobación')
            ->assertSee('2. Acta de cargos y descargos (grabación)')
            ->assertDontSee('>Borrador</span>', false)
            ->assertDontSee('>Generado</span>', false)
            ->assertDontSee('hub-sub">3. Sanción', false)
            ->assertDontSee('hub-sub">1.1 GA-FT-045', false);
    }

    /** @test */
    public function plantilla_registro_devuelve_html_del_formato_elegido()
    {
        $user = $this->makeUser('abogado');

        $this->actingAs($user)
            ->get(route('abogado.registro.plantilla', ['tipo' => 'llamado']))
            ->assertOk()
            ->assertJsonStructure(['html', 'tipo', 'etiqueta', 'slot'])
            ->assertJson([
                'tipo' => 'llamado',
                'slot' => 'resolucion',
            ]);
    }

    /** @test */
    public function apertura_rellena_encabezado_con_datos_del_caso()
    {
        $user = $this->makeUser('abogado');
        $caso = $this->makeCaso($user, [
            'nombre'    => 'Juan Perez Encabezado',
            'cedula'    => '1075234890',
            'modalidad' => 'Conductor',
        ]);

        $this->actingAs($user)
            ->get(route('documentos.edit', [$caso->id, 'disciplinario']))
            ->assertOk()
            ->assertSee('Juan Perez Encabezado', false)
            ->assertSee('1075234890', false)
            ->assertSee('Conductor', false)
            ->assertSee($caso->numeroRadicado(), false)
            ->assertSee(now()->format('d/m/Y'), false);
    }

    /** @test */
    public function plantilla_de_apertura_anticipa_el_siguiente_radicado()
    {
        $user = $this->makeUser('abogado');
        $this->makeCaso($user); // id 1 → el siguiente radicado debe ser ...-002-01

        $radicado = date('Y') . '-' . str_pad((string) \App\Models\ProcesoDisciplinario::siguienteId(), 3, '0', STR_PAD_LEFT) . '-01';

        $html = $this->actingAs($user)
            ->get(route('abogado.registro.plantilla', ['tipo' => 'disciplinario']))
            ->assertOk()
            ->json('html');

        $this->assertStringContainsString($radicado, $html);
        $this->assertStringContainsString(now()->format('d/m/Y'), $html);
    }

    /** @test */
    public function documento_docx_rellena_encabezado_del_trabajador()
    {
        $user = $this->makeUser('abogado');
        $caso = $this->makeCaso($user, [
            'nombre'    => 'Ana Lucia Rojas',
            'cedula'    => '1075234890',
            'modalidad' => 'Taquillera',
        ]);

        $path = app(\App\Services\OfficialDocumentService::class)->materializeDocx($caso, 'disciplinario');
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($path);

        $this->assertStringContainsString('Ana Lucia Rojas', $xml);
        $this->assertStringContainsString('Taquillera', $xml);
        $this->assertStringContainsString('1075234890', $xml);
        $this->assertStringContainsString($caso->numeroRadicado(), $xml);
        $this->assertStringContainsString(now()->format('d/m/Y'), $xml);
        $this->assertStringNotContainsString('202X-XXX', $xml);
    }

    /** @test */
    public function tipo_invalido_devuelve_404()
    {
        $user = $this->makeUser('abogado');
        $caso = $this->makeCaso($user);

        $this->actingAs($user)
            ->get(route('documentos.edit', [$caso->id, 'invalido']))
            ->assertStatus(404);
    }

    // ─────────────────────────────────────────────────────────────────
    // EVIDENCIAS
    // ─────────────────────────────────────────────────────────────────

    /** @test */
    public function evidencia_pdf_se_carga_correctamente()
    {
        Storage::fake('local');

        $user = $this->makeUser('abogado');
        $caso = $this->makeCaso($user);

        $file = UploadedFile::fake()->create('informe.pdf', 500, 'application/pdf');

        $this->actingAs($user)
            ->post(route('documentos.evidencias.store', $caso->id), [
                'archivo'     => $file,
                'descripcion' => 'Informe de prueba',
            ])
            ->assertRedirect(route('documentos.index', $caso->id));

        $this->assertDatabaseHas('caso_evidencias', [
            'caso_id'   => $caso->id,
            'extension' => 'pdf',
        ]);
    }

    /** @test */
    public function evidencia_jpg_se_carga_correctamente()
    {
        Storage::fake('local');

        $user = $this->makeUser('abogado');
        $caso = $this->makeCaso($user);

        $file = UploadedFile::fake()->image('foto.jpg');

        $this->actingAs($user)
            ->post(route('documentos.evidencias.store', $caso->id), [
                'archivo' => $file,
            ])
            ->assertRedirect(route('documentos.index', $caso->id));

        $this->assertDatabaseHas('caso_evidencias', [
            'caso_id'   => $caso->id,
            'extension' => 'jpg',
        ]);
    }

    /** @test */
    public function evidencia_png_se_carga_correctamente()
    {
        Storage::fake('local');

        $user = $this->makeUser('abogado');
        $caso = $this->makeCaso($user);

        $file = UploadedFile::fake()->image('captura.png', 800, 600);

        $this->actingAs($user)
            ->post(route('documentos.evidencias.store', $caso->id), [
                'archivo' => $file,
            ])
            ->assertRedirect(route('documentos.index', $caso->id));
    }

    /** @test */
    public function video_es_rechazado()
    {
        Storage::fake('local');

        $user = $this->makeUser('abogado');
        $caso = $this->makeCaso($user);

        $file = UploadedFile::fake()->create('video.mp4', 2000, 'video/mp4');

        $this->actingAs($user)
            ->post(route('documentos.evidencias.store', $caso->id), [
                'archivo' => $file,
            ])
            ->assertSessionHasErrors('archivo');
    }

    /** @test */
    public function archivo_ejecutable_es_rechazado()
    {
        Storage::fake('local');

        $user = $this->makeUser('abogado');
        $caso = $this->makeCaso($user);

        $file = UploadedFile::fake()->create('virus.exe', 100, 'application/x-ms-dos-executable');

        $this->actingAs($user)
            ->post(route('documentos.evidencias.store', $caso->id), [
                'archivo' => $file,
            ])
            ->assertSessionHasErrors('archivo');
    }

    /** @test */
    public function abogado_no_puede_cargar_evidencia_en_caso_ajeno()
    {
        Storage::fake('local');

        $abog1 = $this->makeUser('abogado');
        $abog2 = $this->makeUser('abogado');
        $caso  = $this->makeCaso($abog2);

        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $this->actingAs($abog1)
            ->post(route('documentos.evidencias.store', $caso->id), [
                'archivo' => $file,
            ])
            ->assertStatus(404);
    }

    /** @test */
    public function abogado_no_puede_descargar_evidencia_de_caso_ajeno()
    {
        Storage::fake('local');

        $abog1 = $this->makeUser('abogado');
        $abog2 = $this->makeUser('abogado');
        $caso  = $this->makeCaso($abog2);

        $ev = CasoEvidencia::create([
            'caso_id'           => $caso->id,
            'user_id'           => $abog2->id,
            'nombre_original'   => 'doc.pdf',
            'nombre_almacenado' => 'doc.pdf',
            'extension'         => 'pdf',
            'mime_type'         => 'application/pdf',
            'tamano'            => 1024,
            'ruta_segura'       => 'evidencias/' . $caso->id . '/doc.pdf',
        ]);

        $this->actingAs($abog1)
            ->get(route('documentos.evidencias.download', [$caso->id, $ev->id]))
            ->assertStatus(404);
    }

    /** @test */
    public function abogado_no_puede_eliminar_evidencia_de_otro_abogado()
    {
        Storage::fake('local');

        $abog1 = $this->makeUser('abogado');
        $abog2 = $this->makeUser('abogado');
        $caso  = $this->makeCaso($abog1); // caso de abog1

        // Evidencia cargada por abog2 en el caso de abog1 (hipotético)
        $ev = CasoEvidencia::create([
            'caso_id'           => $caso->id,
            'user_id'           => $abog2->id,
            'nombre_original'   => 'doc.pdf',
            'nombre_almacenado' => 'doc.pdf',
            'extension'         => 'pdf',
            'mime_type'         => 'application/pdf',
            'tamano'            => 1024,
            'ruta_segura'       => 'evidencias/' . $caso->id . '/doc.pdf',
        ]);

        // abog1 intenta borrar evidencia de abog2
        $this->actingAs($abog1)
            ->delete(route('documentos.evidencias.destroy', [$caso->id, $ev->id]))
            ->assertRedirect(route('abogado.dashboard'));
    }

    // ─────────────────────────────────────────────────────────────────
    // ESTADO DEL DOCUMENTO
    // ─────────────────────────────────────────────────────────────────

    /** @test */
    public function estado_inicial_es_no_iniciado()
    {
        $user = $this->makeUser('abogado');
        $caso = $this->makeCaso($user);

        $estado = $caso->estadoDocumento('disciplinario');
        $this->assertSame('no_iniciado', $estado->estado);
    }

    /** @test */
    public function dos_llamadas_a_estadoDocumento_devuelven_mismo_registro()
    {
        $user = $this->makeUser('abogado');
        $caso = $this->makeCaso($user);

        $a = $caso->estadoDocumento('acta');
        $b = $caso->estadoDocumento('acta');

        $this->assertSame($a->id, $b->id);
    }

    /** @test */
    public function detalle_sin_documento_generado_se_queda_pendiente_sin_enviar()
    {
        $user = $this->makeUser('abogado');
        $caso = $this->makeCaso($user, ['estado' => 'Pendiente']);

        $this->actingAs($user)
            ->get(route('abogado.detalleproceso', $caso->id))
            ->assertOk()
            ->assertDontSee('form-enviar-proceso', false)
            ->assertSee('sipd-estado--pendiente', false)
            ->assertSee('Pendiente');

        $this->actingAs($user)
            ->put(route('abogado.solicitar_veredicto', $caso->id))
            ->assertRedirect();

        $this->assertSame('Pendiente', $caso->fresh()->estado);
    }

    /** @test */
    public function al_generar_un_documento_se_puede_enviar_a_en_proceso()
    {
        $user = $this->makeUser('abogado');
        $caso = $this->makeCaso($user, ['estado' => 'Pendiente']);
        $caso->estadoDocumento('disciplinario')->update([
            'estado' => 'generado',
            'generado_en' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('abogado.detalleproceso', $caso->id))
            ->assertOk()
            ->assertSee('form-enviar-proceso', false);

        $this->actingAs($user)
            ->put(route('abogado.solicitar_veredicto', $caso->id))
            ->assertRedirect();

        $this->assertSame('En Proceso', $caso->fresh()->estado);
    }

    /** @test */
    public function el_veredicto_pasa_de_en_proceso_a_sancionado_o_archivado()
    {
        $coord = $this->makeUser('coordinadora');
        $rh = $this->makeUser('abogado');
        $caso = $this->makeCaso($rh, ['estado' => 'En Proceso']);

        $this->actingAs($rh)
            ->put(route('abogado.actualizarestado', $caso->id), ['estado' => 'Sancionado'])
            ->assertForbidden();

        $this->actingAs($coord)
            ->put(route('abogado.actualizarestado', $caso->id), ['estado' => 'Sancionado'])
            ->assertRedirect();

        $this->assertSame('Sancionado', $caso->fresh()->estado);

        $otro = $this->makeCaso($rh, ['estado' => 'En Proceso']);
        $this->actingAs($coord)
            ->put(route('abogado.actualizarestado', $otro->id), ['estado' => 'Archivado'])
            ->assertRedirect();

        $this->assertSame('Archivado', $otro->fresh()->estado);
    }

    /** @test */
    public function no_se_puede_sancionar_un_caso_pendiente()
    {
        $coord = $this->makeUser('coordinadora');
        $caso = $this->makeCaso($coord, ['estado' => 'Pendiente']);

        $this->actingAs($coord)
            ->put(route('abogado.actualizarestado', $caso->id), ['estado' => 'Sancionado'])
            ->assertRedirect();

        $this->assertSame('Pendiente', $caso->fresh()->estado);
    }
}

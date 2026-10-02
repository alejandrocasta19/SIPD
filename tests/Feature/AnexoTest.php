<?php

namespace Tests\Feature;

use App\Models\CasoAnexo;
use App\Models\ProcesoDisciplinario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnexoTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role = 'abogado'): User
    {
        return User::factory()->create(['role' => User::normalizarRol($role)]);
    }

    private function makeCaso(User $user): ProcesoDisciplinario
    {
        return ProcesoDisciplinario::factory()->create([
            'user_id' => $user->id,
            'nombre' => 'Conductor Anexo',
            'estado' => 'Pendiente',
        ]);
    }

    private function pdf(string $name, int $kb = 40): UploadedFile
    {
        return UploadedFile::fake()->create($name, $kb, 'application/pdf');
    }

    private function word(string $name = 'oficio.docx'): UploadedFile
    {
        return UploadedFile::fake()->create(
            $name,
            80,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        );
    }

    /** @test */
    public function partes_redirige_a_anexos()
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->get('/abogado/partes')
            ->assertRedirect(route('abogado.anexos'));
    }

    /** @test */
    public function abogado_ve_el_modulo_de_anexos()
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->get(route('abogado.anexos'))
            ->assertOk()
            ->assertSee('Anexos escaneados')
            ->assertSee('5. Terminación por justas causas')
            ->assertSee('1.1 GA-FT-045')
            ->assertSee('2. Acta de cargos y descargos')
            ->assertSee('Decisión de archivo')
            ->assertDontSee('Hay que firmarlo')
            ->assertDontSee('Título (opcional)')
            ->assertDontSee('Partes involucradas');
    }

    /** @test */
    public function abogado_carga_un_formato_oficial_en_su_caso()
    {
        Storage::fake('local');
        $user = $this->makeUser();
        $caso = $this->makeCaso($user);

        $this->actingAs($user)
            ->post(route('abogado.anexos.store'), [
                'caso_id' => $caso->id,
                'tipo' => 'acta',
                'archivo' => $this->word('oficio.docx'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('caso_anexos', [
            'caso_id' => $caso->id,
            'tipo' => 'acta',
            'estado' => 'cargado',
            'titulo' => '2. Acta de cargos y descargos (grabación)',
        ]);
    }

    /** @test */
    public function anexo_aparece_en_detalle_y_documentos_del_caso()
    {
        Storage::fake('local');
        $user = $this->makeUser();
        $caso = $this->makeCaso($user);

        $this->actingAs($user)
            ->post(route('abogado.anexos.store'), [
                'caso_id' => $caso->id,
                'tipo' => 'acta',
                'archivo' => $this->word('oficio.docx'),
            ]);

        $this->actingAs($user)
            ->get(route('abogado.detalleproceso', $caso->id))
            ->assertOk()
            ->assertSee('Anexos del expediente')
            ->assertSee('oficio.docx')
            ->assertSee('2. Acta de cargos y descargos (grabación)');

        $this->actingAs($user)
            ->get(route('documentos.index', $caso->id))
            ->assertOk()
            ->assertSee('Anexos del expediente')
            ->assertSee('oficio.docx');

        $this->actingAs($user)
            ->get(route('documentos.edit', [$caso->id, 'disciplinario']))
            ->assertOk()
            ->assertSee('oficio.docx');

        $this->actingAs($user)
            ->get(route('abogado.consultarproceso'))
            ->assertOk()
            ->assertSee('1 anexo');

        $this->actingAs($user)
            ->get(route('documentos.hub'))
            ->assertOk()
            ->assertSee('1 anexo');
    }

    /** @test */
    public function terminacion_se_carga_ya_firmada()
    {
        Storage::fake('local');
        $user = $this->makeUser();
        $caso = $this->makeCaso($user);

        $this->actingAs($user)
            ->post(route('abogado.anexos.store'), [
                'caso_id' => $caso->id,
                'tipo' => 'terminacion',
                'archivo' => $this->word('terminacion.docx'),
            ])
            ->assertStatus(422);

        $this->actingAs($user)
            ->post(route('abogado.anexos.store'), [
                'caso_id' => $caso->id,
                'tipo' => 'terminacion',
                'archivo' => $this->pdf('terminacion-firmada.pdf', 90),
            ])
            ->assertRedirect();

        $anexo = CasoAnexo::first();
        $this->assertSame('firmado', $anexo->estado);
        $this->assertSame('terminacion', $anexo->tipo);
        $this->assertSame('5. Terminación por justas causas', $anexo->titulo);
        $this->assertSame('terminacion-firmada.pdf', $anexo->nombre_original);
        $this->assertNull($anexo->ruta_firmada);
        $this->assertNotNull($anexo->firmado_at);
        Storage::disk('local')->assertExists($anexo->ruta_segura);

        $this->actingAs($user)
            ->get(route('abogado.detalleproceso', $caso->id))
            ->assertOk()
            ->assertSee('5. Terminación por justas causas')
            ->assertSee('terminacion-firmada.pdf');

        $user->otorgarPermiso('descargar_anexos', null);

        $this->actingAs($user)
            ->get(route('abogado.anexos', ['filtro' => 'resolucion']))
            ->assertOk()
            ->assertSee('Descargar')
            ->assertSee('Editar')
            ->assertSee('Eliminar')
            ->assertDontSee('Original')
            ->assertDontSee('Imprimir')
            ->assertDontSee('Cargar firmado');
    }

    /** @test */
    public function no_se_carga_firmado_sobre_un_archivo_previo()
    {
        Storage::fake('local');
        $user = $this->makeUser();
        $caso = $this->makeCaso($user);

        $this->actingAs($user)
            ->post(route('abogado.anexos.store'), [
                'caso_id' => $caso->id,
                'tipo' => 'acta',
                'archivo' => $this->pdf('oficio.pdf'),
            ]);

        $anexo = CasoAnexo::first();
        $this->actingAs($user)
            ->put(route('abogado.anexos.firmar', $anexo->id), [
                'archivo' => $this->pdf('firmado.pdf'),
            ])
            ->assertStatus(422);
    }

    /** @test */
    public function abogado_no_puede_cargar_anexo_en_caso_ajeno()
    {
        Storage::fake('local');
        $abogado = $this->makeUser();
        $otro = $this->makeUser();
        $caso = $this->makeCaso($otro);

        $this->actingAs($abogado)
            ->post(route('abogado.anexos.store'), [
                'caso_id' => $caso->id,
                'tipo' => 'acta',
                'archivo' => $this->pdf('secreto.pdf'),
            ])
            ->assertStatus(404);
    }

    /** @test */
    public function rh_ve_editar_eliminar_y_descargar_y_pide_permiso_si_no_los_tiene()
    {
        Storage::fake('local');
        $rh = $this->makeUser();
        $coord = $this->makeUser('coordinadora');
        $caso = $this->makeCaso($rh);

        $this->actingAs($coord)
            ->post(route('abogado.anexos.store'), [
                'caso_id' => $caso->id,
                'tipo' => 'acta',
                'archivo' => $this->word('acta-coord.docx'),
            ]);

        $this->actingAs($rh)
            ->get(route('abogado.anexos'))
            ->assertOk()
            ->assertSee('Editar')
            ->assertSee('Eliminar')
            ->assertSee('Descargar')
            ->assertSee("SIPD_abrirPermiso('editar_anexos')", false)
            ->assertSee("SIPD_abrirPermiso('eliminar_anexos')", false)
            ->assertSee("SIPD_abrirPermiso('descargar_anexos')", false)
            ->assertDontSee('<h3>Editar anexo</h3>', false);

        $rh->otorgarPermiso('editar_anexos', null);
        $rh->otorgarPermiso('eliminar_anexos', null);
        $rh->otorgarPermiso('descargar_anexos', null);

        $this->actingAs($rh)
            ->get(route('abogado.anexos'))
            ->assertOk()
            ->assertSee('anexo-editar')
            ->assertSee('Editar anexo')
            ->assertSee(route('abogado.anexos.download', CasoAnexo::first()->id), false)
            ->assertDontSee("SIPD_abrirPermiso('editar_anexos')", false);
    }

    /** @test */
    public function rh_sin_permiso_no_edita_ni_borra_anexo()
    {
        Storage::fake('local');
        $rh = $this->makeUser();
        $coord = $this->makeUser('coordinadora');
        $caso = $this->makeCaso($rh);

        $this->actingAs($coord)
            ->post(route('abogado.anexos.store'), [
                'caso_id' => $caso->id,
                'tipo' => 'acta',
                'archivo' => $this->word('acta-coord.docx'),
            ]);

        $anexo = CasoAnexo::first();

        $this->actingAs($rh)
            ->put(route('abogado.anexos.update', $anexo->id), [
                'tipo' => 'sancion',
            ])
            ->assertRedirect(route('abogado.dashboard'));

        $this->actingAs($rh)
            ->delete(route('abogado.anexos.destroy', $anexo->id))
            ->assertRedirect(route('abogado.dashboard'));

        $this->assertDatabaseHas('caso_anexos', ['id' => $anexo->id, 'tipo' => 'acta']);
    }

    /** @test */
    public function rh_edita_tipo_y_archivo_de_anexo_de_su_caso()
    {
        Storage::fake('local');
        $rh = $this->makeUser();
        $coord = $this->makeUser('coordinadora');
        $caso = $this->makeCaso($rh);

        $this->actingAs($coord)
            ->post(route('abogado.anexos.store'), [
                'caso_id' => $caso->id,
                'tipo' => 'acta',
                'archivo' => $this->word('acta-coord.docx'),
            ]);

        $anexo = CasoAnexo::first();
        $rutaVieja = $anexo->ruta_segura;
        $rh->otorgarPermiso('editar_anexos', null);

        $this->actingAs($rh)
            ->put(route('abogado.anexos.update', $anexo->id), [
                'tipo' => 'sancion',
                'archivo' => $this->pdf('sancion.pdf'),
            ])
            ->assertRedirect();

        $anexo->refresh();
        $this->assertSame('sancion', $anexo->tipo);
        $this->assertSame('sancion.pdf', $anexo->nombre_original);
        Storage::disk('local')->assertMissing($rutaVieja);
        Storage::disk('local')->assertExists($anexo->ruta_segura);
    }

    /** @test */
    public function rh_con_permiso_elimina_anexo_de_su_caso()
    {
        Storage::fake('local');
        $rh = $this->makeUser();
        $coord = $this->makeUser('coordinadora');
        $caso = $this->makeCaso($rh);

        $this->actingAs($coord)
            ->post(route('abogado.anexos.store'), [
                'caso_id' => $caso->id,
                'tipo' => 'acta',
                'archivo' => $this->word('acta-coord.docx'),
            ]);

        $anexo = CasoAnexo::first();
        $rh->otorgarPermiso('eliminar_anexos', null);

        $this->actingAs($rh)
            ->delete(route('abogado.anexos.destroy', $anexo->id))
            ->assertRedirect(route('abogado.anexos'));

        $this->assertDatabaseMissing('caso_anexos', ['id' => $anexo->id]);
    }

    /** @test */
    public function reportes_incluyen_conteo_de_anexos()
    {
        Storage::fake('local');
        $user = $this->makeUser();
        $caso = $this->makeCaso($user);

        $this->actingAs($user)
            ->post(route('abogado.anexos.store'), [
                'caso_id' => $caso->id,
                'tipo' => 'acta',
                'archivo' => $this->word('oficio.docx'),
            ]);

        $this->actingAs($user)
            ->get(route('abogado.reportes.datos'))
            ->assertOk()
            ->assertJsonPath('anexos.total', 1)
            ->assertJsonPath('anexos.archivo_previo', 1)
            ->assertJsonPath('anexos.pendiente_firma', 0)
            ->assertJsonPath('anexos.firmado', 0)
            ->assertJsonStructure(['by_modalidad', 'reincidencias', 'pending_faults', 'monthly', 'states']);

        $this->actingAs($user)
            ->get(route('abogado.reportes'))
            ->assertOk()
            ->assertSee('Archivo previo')
            ->assertSee('Firmados')
            ->assertSee('Anexos')
            ->assertSee('Cargo del trabajador')
            ->assertSee('Tipo de falta')
            ->assertSee('Ampliar vista')
            ->assertDontSee('Pendiente firma')
            ->assertDontSee('Por estado')
            ->assertDontSee('Faltas activas');

        $this->actingAs($user)
            ->get(route('abogado.estadistica'))
            ->assertRedirect(route('abogado.reportes'));
    }
}

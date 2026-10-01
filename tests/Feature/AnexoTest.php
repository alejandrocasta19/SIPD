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
        return User::factory()->create(['role' => $role]);
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

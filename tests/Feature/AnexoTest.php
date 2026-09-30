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
            ->assertSee('Terminación por justas causas')
            ->assertDontSee('Hay que firmarlo')
            ->assertDontSee('Título (opcional)')
            ->assertDontSee('Partes involucradas');
    }

    /** @test */
    public function abogado_carga_archivo_previo_en_su_caso()
    {
        Storage::fake('local');
        $user = $this->makeUser();
        $caso = $this->makeCaso($user);

        $this->actingAs($user)
            ->post(route('abogado.anexos.store'), [
                'caso_id' => $caso->id,
                'tipo' => 'archivo_previo',
                'archivo' => $this->word('oficio.docx'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('caso_anexos', [
            'caso_id' => $caso->id,
            'tipo' => 'archivo_previo',
            'estado' => 'cargado',
            'titulo' => 'oficio.docx',
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
                'tipo' => 'archivo_previo',
                'archivo' => $this->word('oficio.docx'),
            ]);

        $this->actingAs($user)
            ->get(route('abogado.detalleproceso', $caso->id))
            ->assertOk()
            ->assertSee('Anexos del expediente')
            ->assertSee('oficio.docx')
            ->assertSee('Archivo previo');

        $this->actingAs($user)
            ->get(route('documentos.index', $caso->id))
            ->assertOk()
            ->assertSee('Anexos del expediente')
            ->assertSee('oficio.docx');
    }

    /** @test */
    public function terminacion_de_contrato_conserva_el_original_al_cargar_el_escaneo()
    {
        Storage::fake('local');
        $user = $this->makeUser();
        $caso = $this->makeCaso($user);

        $this->actingAs($user)
            ->post(route('abogado.anexos.store'), [
                'caso_id' => $caso->id,
                'tipo' => 'firma_gerente',
                'archivo' => $this->word('terminacion.docx'),
            ])
            ->assertRedirect();

        $anexo = CasoAnexo::first();
        $this->assertSame('pendiente_firma', $anexo->estado);
        $this->assertSame('Terminación por justas causas', $anexo->titulo);
        $original = $anexo->ruta_segura;

        $this->actingAs($user)
            ->put(route('abogado.anexos.firmar', $anexo->id), [
                'archivo' => $this->pdf('firmado.pdf', 90),
            ])
            ->assertRedirect();

        $anexo = $anexo->fresh();
        $this->assertSame('firmado', $anexo->estado);
        $this->assertSame($original, $anexo->ruta_segura);
        $this->assertNotNull($anexo->ruta_firmada);
        $this->assertNotSame($original, $anexo->ruta_firmada);
        Storage::disk('local')->assertExists($original);
        Storage::disk('local')->assertExists($anexo->ruta_firmada);
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
                'tipo' => 'archivo_previo',
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
                'tipo' => 'archivo_previo',
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
                'tipo' => 'archivo_previo',
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
            ->assertSee('Pendiente firma')
            ->assertSee('Anexos')
            ->assertSee('Cargo del trabajador')
            ->assertSee('Tipo de falta')
            ->assertSee('Ver más')
            ->assertDontSee('Por estado')
            ->assertDontSee('Faltas activas');

        $this->actingAs($user)
            ->get(route('abogado.estadistica'))
            ->assertRedirect(route('abogado.reportes'));
    }
}

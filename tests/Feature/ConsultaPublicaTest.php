<?php

namespace Tests\Feature;

use App\Models\CasoDocumentoEstado;
use App\Models\CasoEvidencia;
use App\Models\ProcesoDisciplinario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsultaPublicaTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function muestra_el_formulario_de_consulta()
    {
        $this->get(route('consulta.publica'))
            ->assertOk()
            ->assertSee('Consultar mi caso')
            ->assertSee('Número de cédula');
    }

    /** @test */
    public function consulta_por_cedula_muestra_avance_sin_datos_reservados()
    {
        $secreto = 'El conductor golpeó el vehículo de placas ABC123 y amenazó al pasajero.';
        $descargos = 'Niego los hechos y pido copias de las pruebas internas.';
        $decision = 'Sanción de 15 días y descuento de $2.500.000';

        $proceso = ProcesoDisciplinario::factory()->create([
            'nombre' => 'Carlos Pérez',
            'cedula' => '1075123456',
            'placa' => 'XYZ987',
            'telefono' => '3001112233',
            'tipo_falta' => 'Falta grave por agresión',
            'descripcion_falta' => $secreto,
            'observacion' => 'Anotación reservada de RH',
            'descargos' => $descargos,
            'decision_final' => $decision,
            'estado' => 'En Proceso',
            'tipo_proceso' => 'disciplinario',
        ]);

        CasoDocumentoEstado::create([
            'caso_id' => $proceso->id,
            'tipo_documento' => 'disciplinario',
            'estado' => 'generado',
            'generado_en' => now()->subDays(2),
        ]);

        CasoEvidencia::create([
            'caso_id' => $proceso->id,
            'user_id' => User::factory()->create()->id,
            'nombre_original' => 'video-interno-camara.mp4',
            'nombre_almacenado' => 'oculto.bin',
            'extension' => 'jpg',
            'mime_type' => 'image/jpeg',
            'tamano' => 1024,
            'descripcion' => 'Grabación confidencial',
            'ruta_segura' => 'evidencias/oculto.bin',
        ]);

        $this->from(route('consulta.publica'))
            ->post(route('consulta.publica.buscar'), ['cedula' => '1075123456'])
            ->assertRedirect(route('consulta.publica'));

        $this->get(route('consulta.publica'))
            ->assertOk()
            ->assertSee('PRO-' . str_pad($proceso->id, 3, '0', STR_PAD_LEFT))
            ->assertSee('Carlos Pérez')
            ->assertSee('Proceso disciplinario')
            ->assertSee('En Proceso')
            ->assertSee('Línea de tiempo')
            ->assertSee('Apertura')
            ->assertSee('Descargos')
            ->assertSee('Se incorporó un elemento de prueba al expediente.')
            ->assertDontSee($secreto)
            ->assertDontSee($descargos)
            ->assertDontSee($decision)
            ->assertDontSee('Anotación reservada de RH')
            ->assertDontSee('Falta grave por agresión')
            ->assertDontSee('XYZ987')
            ->assertDontSee('3001112233')
            ->assertDontSee('video-interno-camara.mp4')
            ->assertDontSee('Grabación confidencial');
    }

    /** @test */
    public function consulta_sin_resultados_no_expone_casos_ajenos()
    {
        ProcesoDisciplinario::factory()->create([
            'cedula' => '1111111111',
            'nombre' => 'Conductor Ajeno',
            'descripcion_falta' => 'Hecho reservado de otra persona',
        ]);

        $this->post(route('consulta.publica.buscar'), ['cedula' => '9999999999'])
            ->assertRedirect(route('consulta.publica'));

        $this->get(route('consulta.publica'))
            ->assertOk()
            ->assertSee('No se encontraron procesos con esa cédula.')
            ->assertDontSee('Conductor Ajeno')
            ->assertDontSee('Hecho reservado de otra persona');
    }
}

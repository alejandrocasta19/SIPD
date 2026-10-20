<?php

namespace Tests\Feature;

use App\Models\ProcesoDisciplinario;
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
            ->assertSee('Número de cédula')
            ->assertDontSee('id="sipd-intro"', false)
            ->assertDontSee('INTRO%202023%20COOTRANSHUILA%20NO%20AUDIO-vidkit-2026-10-13.webm', false);
    }

    /** @test */
    public function la_consulta_por_cedula_no_confirma_ni_muestra_detalles_del_expediente()
    {
        $proceso = ProcesoDisciplinario::factory()->create([
            'nombre' => 'Caso de prueba sin identidad real',
            'cedula' => '9876543210',
            'descripcion_falta' => 'DETALLE_PRIVADO_DE_PRUEBA',
            'estado' => 'En Proceso',
            'tipo_proceso' => 'disciplinario',
        ]);

        $this->from(route('consulta.publica'))
            ->post(route('consulta.publica.buscar'), ['cedula' => '9876543210'])
            ->assertRedirect(route('consulta.publica'));

        $this->get(route('consulta.publica'))
            ->assertOk()
            ->assertSee('no podemos confirmar si existe un expediente')
            ->assertDontSee('DETALLE_PRIVADO_DE_PRUEBA')
            ->assertDontSee('Caso de prueba sin identidad real')
            ->assertDontSee('PRO-' . str_pad($proceso->id, 3, '0', STR_PAD_LEFT))
            ->assertDontSee('En Proceso')
            ->assertDontSee('9876543210')
            ->assertDontSee('Ver trámite')
            ->assertDontSee('Línea de tiempo');
    }

    /** @test */
    public function la_respuesta_es_igual_para_coincidencias_y_sin_coincidencias()
    {
        ProcesoDisciplinario::factory()->create([
            'cedula' => '1111111111',
            'nombre' => 'Otro caso de prueba',
        ]);

        $this->post(route('consulta.publica.buscar'), ['cedula' => '1111111111'])
            ->assertRedirect(route('consulta.publica'));
        $respuestaConCoincidencia = $this->get(route('consulta.publica'))->assertOk();

        $this->post(route('consulta.publica.buscar'), ['cedula' => '2222222222'])
            ->assertRedirect(route('consulta.publica'));
        $respuestaSinCoincidencia = $this->get(route('consulta.publica'))->assertOk();

        $respuestaConCoincidencia->assertSee('no podemos confirmar si existe un expediente');
        $respuestaSinCoincidencia->assertSee('no podemos confirmar si existe un expediente');
        $respuestaConCoincidencia->assertDontSee('Otro caso de prueba');
        $respuestaSinCoincidencia->assertDontSee('Otro caso de prueba');
    }

    /** @test */
    public function la_cedula_no_se_conserva_en_la_sesion_ni_se_devuelve_en_la_pagina()
    {
        $cedula = '9876543210';

        $this->from(route('consulta.publica'))
            ->post(route('consulta.publica.buscar'), ['cedula' => $cedula])
            ->assertRedirect(route('consulta.publica'))
            ->assertSessionMissing('consulta_publica_cedula');

        $this->get(route('consulta.publica'))
            ->assertOk()
            ->assertDontSee($cedula)
            ->assertDontSee('value="' . $cedula . '"', false);
    }

    /** @test */
    public function consulta_acepta_cedula_con_puntos()
    {
        $this->from(route('consulta.publica'))
            ->post(route('consulta.publica.buscar'), ['cedula' => '987.654.3210'])
            ->assertRedirect(route('consulta.publica'));

        $this->get(route('consulta.publica'))
            ->assertOk()
            ->assertSee('no podemos confirmar si existe un expediente')
            ->assertDontSee('987.654.3210');
    }

    /** @test */
    public function las_entradas_malformadas_no_se_reflejan_en_la_respuesta()
    {
        $malformed = '<script>invalid-input</script>';

        $this->from(route('consulta.publica'))
            ->post(route('consulta.publica.buscar'), ['cedula' => $malformed])
            ->assertRedirect(route('consulta.publica'))
            ->assertSessionHasErrors('cedula')
            ->assertSessionMissing('_old_input');

        $this->get(route('consulta.publica'))
            ->assertOk()
            ->assertDontSee($malformed, false);
    }

    /** @test */
    public function los_intentos_repetidos_siguen_limitados_por_ip()
    {
        for ($intento = 0; $intento < 10; $intento++) {
            $this->post(route('consulta.publica.buscar'), ['cedula' => '1234567890'])
                ->assertRedirect(route('consulta.publica'));
        }

        $this->post(route('consulta.publica.buscar'), ['cedula' => '1234567890'])
            ->assertStatus(429);
    }

    /** @test */
    public function login_muestra_acceso_institucional_y_consulta_publica()
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Acceso al sistema')
            ->assertSee('Consultar mi caso')
            ->assertSee('id="sipd-intro"', false)
            ->assertSee('data-src=', false)
            ->assertDontSee('<source src=', false)
            ->assertSee('class="btn-public"', false)
            ->assertSee('ACCESO PÚBLICO')
            ->assertSee('Cootranshuila')
            ->assertSee('logo-sipd.png', false)
            ->assertDontSee('logo-sipd.svg', false)
            ->assertDontSee('Acceso rápido')
            ->assertDontSee('data-password=', false)
            ->assertDontSee('kellyrodriguez@sipd.co', false)
            ->assertDontSee('marshallrincon@sipd.co', false)
            ->assertDontSee('jorgerosado@sipd.co', false);
    }

    /** @test */
    public function accesos_rapidos_se_muestran_solo_en_entorno_local_con_credenciales_configuradas()
    {
        app()->detectEnvironment(function () {
            return 'local';
        });
        config([
            'sipd.quick_access' => [[
                'label' => 'Cuenta temporal',
                'email' => 'temporal@sipd.co',
                'password' => 'temporary-test-password',
            ]],
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Acceso rápido · solo desarrollo local')
            ->assertSee('temporal@sipd.co')
            ->assertSee('data-password="temporary-test-password"', false);
    }

    /** @test */
    public function seeder_no_crea_cuentas_con_credenciales_predeterminadas()
    {
        $this->seed();

        $this->assertDatabaseCount('users', 0);
    }
}

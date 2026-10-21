<?php

namespace Tests\Feature;

use App\Models\Aviso;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificacionesPaginacionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function pagina_los_avisos_de_cinco_en_cinco_y_excluye_actividad_de_sesion()
    {
        $coordinadora = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $equipo = User::factory()->create(['role' => User::ROLE_EQUIPO]);
        $avisoIds = [];

        for ($i = 1; $i <= 6; $i++) {
            $aviso = Aviso::enviar($coordinadora, [
                'remitente_id' => $equipo->id,
                'tipo' => Aviso::TIPO_AVISO,
                'titulo' => 'Aviso visible ' . $i,
                'cuerpo' => 'Contenido de prueba',
            ]);
            $avisoIds[$i] = $aviso->id;
        }

        for ($i = 1; $i <= 5; $i++) {
            Aviso::enviar($coordinadora, [
                'remitente_id' => $equipo->id,
                'tipo' => Aviso::TIPO_SESION,
                'titulo' => 'Actividad de sesión ' . $i,
                'cuerpo' => 'Evento interno no listado',
            ]);
        }

        $paginaUno = $this->actingAs($coordinadora)
            ->get(route('notificaciones.index'))
            ->assertOk()
            ->assertSee('6 avisos en la bandeja')
            ->assertDontSee('Actividad de sesión')
            ->assertSee('href="' . route('notificaciones.index', ['page' => 2]) . '"', false);

        foreach (range(2, 6) as $i) {
            $paginaUno->assertSee('id="aviso-' . $avisoIds[$i] . '"', false);
        }
        $paginaUno->assertDontSee('id="aviso-' . $avisoIds[1] . '"', false);

        $paginaDos = $this->get(route('notificaciones.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('Anterior')
            ->assertDontSee('href="' . route('notificaciones.index', ['page' => 3]) . '"', false);
        $paginaDos->assertSee('id="aviso-' . $avisoIds[1] . '"', false);

        foreach (range(2, 6) as $i) {
            $paginaDos->assertDontSee('id="aviso-' . $avisoIds[$i] . '"', false);
        }
    }

    /** @test */
    public function el_contador_y_el_desplegable_no_cuentan_actividad_de_sesion()
    {
        $coordinadora = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $equipo = User::factory()->create(['role' => User::ROLE_EQUIPO]);

        Aviso::enviar($coordinadora, [
            'remitente_id' => $equipo->id,
            'tipo' => Aviso::TIPO_AVISO,
            'titulo' => 'Aviso visible',
            'cuerpo' => 'Contenido de prueba',
        ]);

        Aviso::enviar($coordinadora, [
            'remitente_id' => $equipo->id,
            'tipo' => Aviso::TIPO_SESION,
            'titulo' => 'Actividad de sesión oculta',
            'cuerpo' => 'Evento interno no listado',
        ]);

        $this->actingAs($coordinadora)
            ->get(route('notificaciones.index'))
            ->assertOk()
            ->assertSee('1 nueva')
            ->assertSee('Aviso visible')
            ->assertDontSee('Actividad de sesión oculta');
    }
}

<?php

namespace Tests\Feature;

use App\Http\Middleware\CerrarSesionPorInactividad;
use App\Models\Aviso;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InactividadTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** @test */
    public function una_sesion_con_movimiento_reciente_sigue_abierta()
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $ahora = Carbon::parse('2026-10-02 12:00:00');
        Carbon::setTestNow($ahora);

        $this->actingAs($user)
            ->withSession([CerrarSesionPorInactividad::CLAVE => $ahora->copy()->subMinutes(29)->getTimestamp()])
            ->get(route('abogado.dashboard'))
            ->assertOk();

        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function cierra_la_sesion_a_los_30_minutos_sin_actividad()
    {
        $coord = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['role' => User::ROLE_EQUIPO]);
        $ahora = Carbon::parse('2026-10-02 12:00:00');
        Carbon::setTestNow($ahora);

        $this->actingAs($user)
            ->withSession([CerrarSesionPorInactividad::CLAVE => $ahora->copy()->subMinutes(30)->getTimestamp()])
            ->get(route('abogado.dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', CerrarSesionPorInactividad::MENSAJE);

        $this->assertGuest();
        $this->assertDatabaseHas('sipd_avisos', [
            'user_id' => $coord->id,
            'tipo' => Aviso::TIPO_SESION,
            'remitente_id' => $user->id,
        ]);
        $this->assertStringContainsString(
            'Fecha y hora: 02/10/2026 12:00:00.',
            Aviso::query()->where('user_id', $coord->id)->firstOrFail()->cuerpo
        );
    }

    /** @test */
    public function login_y_logout_del_equipo_notifican_a_la_coordinadora_con_fecha_y_hora()
    {
        $coord = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['role' => User::ROLE_EQUIPO]);
        Carbon::setTestNow(Carbon::parse('2026-10-02 12:00:00'));

        $this->from(route('login'))
            ->post(route('login'), [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect('/abogado');

        $this->assertDatabaseHas('sipd_avisos', [
            'user_id' => $coord->id,
            'tipo' => Aviso::TIPO_SESION,
            'remitente_id' => $user->id,
            'titulo' => 'Inicio de sesión del equipo',
        ]);

        $this->post(route('logout'))->assertRedirect('/');

        $this->assertDatabaseCount('sipd_avisos', 2);
        $this->assertDatabaseHas('sipd_avisos', [
            'user_id' => $coord->id,
            'tipo' => Aviso::TIPO_SESION,
            'remitente_id' => $user->id,
            'titulo' => 'Cierre de sesión del equipo',
        ]);
    }

    /** @test */
    public function el_login_muestra_el_aviso_de_inactividad()
    {
        $user = User::factory()->create();
        $ahora = Carbon::parse('2026-10-02 12:00:00');
        Carbon::setTestNow($ahora);

        $this->actingAs($user)
            ->withSession([CerrarSesionPorInactividad::CLAVE => $ahora->copy()->subMinutes(31)->getTimestamp()])
            ->followingRedirects()
            ->get(route('abogado.dashboard'))
            ->assertOk()
            ->assertSee(CerrarSesionPorInactividad::MENSAJE);
    }

    /** @test */
    public function el_pulso_de_actividad_mantiene_la_sesion()
    {
        $user = User::factory()->create();
        $ahora = Carbon::parse('2026-10-02 12:00:00');
        Carbon::setTestNow($ahora);

        $this->actingAs($user)
            ->withSession([CerrarSesionPorInactividad::CLAVE => $ahora->copy()->subMinutes(10)->getTimestamp()])
            ->postJson(route('sesion.actividad'))
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    /** @test */
    public function la_pantalla_autenticada_vigila_los_30_minutos()
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($user)
            ->get(route('abogado.dashboard'))
            ->assertOk()
            ->assertSee('id="inactividad-form"', false)
            ->assertSee('30 * 60 * 1000', false)
            ->assertSee('sesion\/actividad', false);
    }
}

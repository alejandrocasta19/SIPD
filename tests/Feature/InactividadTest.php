<?php

namespace Tests\Feature;

use App\Http\Middleware\CerrarSesionPorInactividad;
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
        $user = User::factory()->create(['role' => User::ROLE_EQUIPO]);
        $ahora = Carbon::parse('2026-10-02 12:00:00');
        Carbon::setTestNow($ahora);

        $this->actingAs($user)
            ->withSession([CerrarSesionPorInactividad::CLAVE => $ahora->copy()->subMinutes(30)->getTimestamp()])
            ->get(route('abogado.dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', CerrarSesionPorInactividad::MENSAJE);

        $this->assertGuest();
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

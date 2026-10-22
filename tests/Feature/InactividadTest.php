<?php

namespace Tests\Feature;

use App\Http\Middleware\CerrarSesionPorInactividad;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Cookie\CookieValuePrefix;
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
        User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['role' => User::ROLE_EQUIPO]);
        $ahora = Carbon::parse('2026-10-02 12:00:00');
        Carbon::setTestNow($ahora);

        $this->actingAs($user)
            ->withSession([CerrarSesionPorInactividad::CLAVE => $ahora->copy()->subMinutes(30)->getTimestamp()])
            ->get(route('abogado.dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', CerrarSesionPorInactividad::MENSAJE);

        $this->assertGuest();
        $this->assertDatabaseCount('sipd_avisos', 0);
    }

    /** @test */
    public function login_y_logout_del_equipo_no_generan_notificaciones_de_trabajo()
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

        $this->post(route('logout'))->assertRedirect('/');

        $this->assertDatabaseCount('sipd_avisos', 0);
    }

    /** @test */
    public function la_cookie_de_sesion_expira_al_cerrar_el_navegador()
    {
        $user = User::factory()->create(['role' => User::ROLE_EQUIPO]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $cookie = collect($response->headers->getCookies())
            ->first(function ($cookie) {
                return $cookie->getName() === config('session.cookie');
            });

        $this->assertNotNull($cookie);
        $this->assertSame(0, $cookie->getExpiresTime());
    }

    /** @test */
    public function ignora_y_elimina_cookies_recordar_sesion_anteriores()
    {
        $user = User::factory()->create(['role' => User::ROLE_EQUIPO]);
        $user->setRememberToken('token-recordado-anterior');
        $user->save();

        $rememberCookie = app('auth')->guard('web')->getRecallerName();
        $rememberValue = $user->id . '|token-recordado-anterior|' . $user->password;
        $encryptedRememberCookie = app('encrypter')->encrypt(
            CookieValuePrefix::create($rememberCookie, app('encrypter')->getKey()) . $rememberValue,
            false
        );
        $response = $this->withCookie($rememberCookie, $encryptedRememberCookie)
            ->get(route('login'));

        $response->assertOk()
            ->assertSee('Acceso al sistema')
            ->assertDontSee('Recordar sesión');

        $this->assertGuest();
        $forgottenCookie = collect($response->headers->getCookies())
            ->first(fn ($cookie) => $cookie->getName() === $rememberCookie);
        $this->assertNotNull($forgottenCookie);
        $this->assertLessThan(time(), $forgottenCookie->getExpiresTime());
    }

    /** @test */
    public function ingresar_a_la_url_raiz_muestra_login_y_cierra_la_sesion_anterior()
    {
        $user = User::factory()->create(['role' => User::ROLE_EQUIPO]);

        $this->actingAs($user)
            ->followingRedirects()
            ->get('/')
            ->assertOk()
            ->assertSee('Acceso al sistema');

        $this->assertGuest();
    }

    /** @test */
    public function no_emite_cookie_persistente_aunque_el_login_reciba_remember()
    {
        $user = User::factory()->create(['role' => User::ROLE_EQUIPO]);

        $response = $this->from(route('login'))
            ->post(route('login'), [
                'email' => $user->email,
                'password' => 'password',
                'remember' => '1',
            ])
            ->assertRedirect('/abogado');

        $rememberCookie = app('auth')->guard('web')->getRecallerName();
        $issuedRememberCookie = collect($response->headers->getCookies())
            ->first(fn ($cookie) => $cookie->getName() === $rememberCookie);
        $this->assertNull($issuedRememberCookie);
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

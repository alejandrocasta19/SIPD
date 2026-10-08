<?php

namespace Tests\Feature;

use App\Models\Aviso;
use App\Models\RecuperacionContrasena;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetViewTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function recuperar_contrasena_usa_la_interfaz_institucional()
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Recuperar contraseña')
            ->assertSee('Correo electrónico')
            ->assertSee('Solicitar recuperación')
            ->assertSee('La coordinadora revisará la solicitud')
            ->assertSee('Volver al acceso')
            ->assertSee('guest-split', false)
            ->assertDontSee('Reset Password')
            ->assertDontSee('Send Password Reset Link');
    }

    /** @test */
    public function solicitud_de_recuperacion_notifica_a_coordinacion_sin_enviar_el_enlace()
    {
        Notification::fake();
        $coordinadora = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $usuario = User::factory()->create(['role' => User::ROLE_EQUIPO]);

        $this->post(route('password.email'), ['email' => $usuario->email])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status', 'passwords.sent');

        $solicitud = RecuperacionContrasena::where('user_id', $usuario->id)->firstOrFail();
        $this->assertSame(RecuperacionContrasena::PENDIENTE, $solicitud->estado);
        $this->assertDatabaseHas('sipd_avisos', [
            'user_id' => $coordinadora->id,
            'remitente_id' => $usuario->id,
            'tipo' => Aviso::TIPO_RECUPERACION,
        ]);
        $this->assertDatabaseMissing('password_resets', ['email' => $usuario->email]);
        Notification::assertNothingSent();
    }

    /** @test */
    public function coordinadora_aprueba_y_el_sistema_envia_el_enlace_de_un_solo_uso()
    {
        Notification::fake();
        $coordinadora = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $usuario = User::factory()->create(['role' => User::ROLE_EQUIPO]);

        $this->post(route('password.email'), ['email' => $usuario->email]);
        $solicitud = RecuperacionContrasena::where('user_id', $usuario->id)->firstOrFail();
        $aviso = Aviso::where('user_id', $coordinadora->id)
            ->where('tipo', Aviso::TIPO_RECUPERACION)
            ->firstOrFail();

        $this->actingAs($coordinadora)
            ->get(route('notificaciones.leer', $aviso->id))
            ->assertRedirect(route('coordinadora.recuperaciones'));

        $this->actingAs($coordinadora)
            ->get(route('coordinadora.recuperaciones'))
            ->assertOk()
            ->assertSee($usuario->email)
            ->assertSee('Aprobar y enviar enlace');

        $this->put(route('coordinadora.recuperaciones.responder', $solicitud->id), [
            'accion' => 'enviar_enlace',
        ])->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('sipd_recuperaciones_contrasena', [
            'id' => $solicitud->id,
            'estado' => RecuperacionContrasena::ENLACE_ENVIADO,
            'responded_by' => $coordinadora->id,
        ]);
        $this->assertDatabaseHas('password_resets', ['email' => $usuario->email]);
        Notification::assertSentTo($usuario, ResetPassword::class);
    }

    /** @test */
    public function solicitud_de_correo_inexistente_no_revela_si_hay_una_cuenta()
    {
        $this->post(route('password.email'), ['email' => 'no-existe@example.test'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status', 'passwords.sent');

        $this->assertDatabaseCount('sipd_recuperaciones_contrasena', 0);
        $this->assertDatabaseCount('sipd_avisos', 0);
    }

    /** @test */
    public function una_solicitud_pendiente_no_genera_avisos_duplicados()
    {
        $coordinadora = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $usuario = User::factory()->create(['role' => User::ROLE_EQUIPO]);

        $this->post(route('password.email'), ['email' => $usuario->email]);
        $this->post(route('password.email'), ['email' => $usuario->email]);

        $this->assertDatabaseCount('sipd_recuperaciones_contrasena', 1);
        $this->assertDatabaseHas('sipd_avisos', [
            'user_id' => $coordinadora->id,
            'tipo' => Aviso::TIPO_RECUPERACION,
        ]);
        $this->assertSame(1, Aviso::where('user_id', $coordinadora->id)
            ->where('tipo', Aviso::TIPO_RECUPERACION)->count());
    }

    /** @test */
    public function solamente_la_coordinadora_puede_revisar_o_resolver_recuperaciones()
    {
        $equipo = User::factory()->create(['role' => User::ROLE_EQUIPO]);
        $solicitud = RecuperacionContrasena::create([
            'user_id' => $equipo->id,
            'estado' => RecuperacionContrasena::PENDIENTE,
        ]);

        $this->actingAs($equipo)
            ->get(route('coordinadora.recuperaciones'))
            ->assertRedirect('/');

        $this->put(route('coordinadora.recuperaciones.responder', $solicitud->id), [
            'accion' => 'enviar_enlace',
        ])->assertRedirect('/');

        $this->assertSame(
            RecuperacionContrasena::PENDIENTE,
            $solicitud->fresh()->estado
        );
    }
}

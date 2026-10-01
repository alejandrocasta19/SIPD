<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('Enviar enlace')
            ->assertSee('Volver al acceso')
            ->assertSee('guest-split', false)
            ->assertDontSee('Reset Password')
            ->assertDontSee('Send Password Reset Link');
    }
}

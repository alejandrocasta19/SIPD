<?php

namespace Tests\Feature;

use Tests\TestCase;

class ConsultaPublicaRateLimitTest extends TestCase
{
    /** @test */
    public function limita_a_diez_consultas_publicas_por_minuto_y_direccion_ip()
    {
        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $this->post(route('consulta.publica.buscar'), ['cedula' => '12345678'])
                ->assertRedirect(route('consulta.publica'));
        }

        $this->post(route('consulta.publica.buscar'), ['cedula' => '12345678'])
            ->assertStatus(429);
    }
}

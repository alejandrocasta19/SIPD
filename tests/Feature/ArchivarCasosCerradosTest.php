<?php

namespace Tests\Feature;

use App\Models\ProcesoDisciplinario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArchivarCasosCerradosTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function archiva_casos_cerrados_y_solo_los_conserva_en_reportes()
    {
        $coordinator = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $closed = ProcesoDisciplinario::factory()->create([
            'user_id' => $coordinator->id,
            'nombre' => 'Caso cerrado conservado',
            'cedula' => '12345678',
            'estado' => 'Sancionado',
        ]);
        $open = ProcesoDisciplinario::factory()->create([
            'user_id' => $coordinator->id,
            'nombre' => 'Caso en curso',
            'estado' => 'En Proceso',
        ]);

        $this->artisan('sipd:archive-closed-cases')->assertExitCode(0);

        $this->assertSoftDeleted('disciplinario', ['id' => $closed->id]);
        $this->assertDatabaseHas('disciplinario', [
            'id' => $open->id,
            'deleted_at' => null,
        ]);

        $this->actingAs($coordinator)
            ->get(route('abogado.reportes'))
            ->assertOk()
            ->assertSee('Caso cerrado conservado');

        $this->actingAs($coordinator)
            ->get(route('abogado.reincidencias'))
            ->assertOk()
            ->assertDontSee('Caso cerrado conservado');

        $this->post(route('consulta.publica.buscar'), ['cedula' => '12345678'])
            ->assertRedirect(route('consulta.publica'));
        $this->get(route('consulta.publica'))
            ->assertOk()
            ->assertDontSee('Caso cerrado conservado');
    }
}

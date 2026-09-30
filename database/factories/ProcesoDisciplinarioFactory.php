<?php

namespace Database\Factories;

use App\Models\ProcesoDisciplinario;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProcesoDisciplinarioFactory extends Factory
{
    protected $model = ProcesoDisciplinario::class;

    public function definition()
    {
        return [
            'nombre'           => $this->faker->name(),
            'cedula'           => $this->faker->numerify('##########'),
            'placa'            => strtoupper($this->faker->bothify('???###')),
            'ruta'             => 'Neiva - Pitalito',
            'telefono'         => $this->faker->phoneNumber(),
            'modalidad'        => 'Premium',
            'tipo_proceso'     => 'disciplinario',
            'fecha_falta'      => now()->format('Y-m-d'),
            'tipo_falta'       => 'Incumplimiento de horario',
            'descripcion_falta'=> $this->faker->paragraph(),
            'estado'           => 'Pendiente',
            'user_id'          => User::factory(),
            'datos_oficiales'  => [
                'yellow_blocks' => [
                    'disciplinario' => array_fill(0, 8, 'Bloque de prueba'),
                    'comprobacion'  => array_fill(0, 25, 'Bloque de prueba'),
                    'acta'          => array_fill(0, 9, 'Bloque de prueba'),
                ]
            ],
        ];
    }
}

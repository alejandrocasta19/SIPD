<?php

namespace App\Support;

class Formatos
{
    /** Letras, espacios y tildes. Sin números. */
    public const NOMBRE = '/^[\p{L}]+(?:[ \'][\p{L}]+)*$/u';

    /** Solo dígitos. */
    public const CEDULA = '/^[0-9]+$/';

    /** Solo dígitos, si se diligencia. */
    public const TELEFONO = '/^[0-9]+$/';

    /** Tres letras, guion y tres números. Ejemplo: ABC-123. */
    public const PLACA = '/^[A-Z]{3}-[0-9]{3}$/';

    /** texto@sipd.co */
    public const EMAIL = '/^[A-Za-z]+@sipd\.co$/';

    public static function reglasNombre(): array
    {
        return ['required', 'string', 'max:255', 'regex:' . self::NOMBRE];
    }

    public static function reglasCedula(bool $obligatoria = true): array
    {
        return [$obligatoria ? 'required' : 'nullable', 'regex:' . self::CEDULA];
    }

    public static function reglasTelefono(): array
    {
        return ['nullable', 'regex:' . self::TELEFONO];
    }

    public static function reglasCargo(): array
    {
        return ['required', 'string', 'max:255', 'regex:' . self::NOMBRE];
    }

    public static function reglasPlaca(): array
    {
        return ['required', 'regex:' . self::PLACA];
    }

    public static function reglasEmail(bool $unica = true, ?int $ignorarId = null): array
    {
        $reglas = ['required', 'regex:' . self::EMAIL, 'max:255'];
        if ($unica) {
            $reglas[] = $ignorarId
                ? 'unique:users,email,' . $ignorarId
                : 'unique:users,email';
        }

        return $reglas;
    }

    public static function placaNormalizada(?string $placa): string
    {
        $placa = strtoupper(trim((string) $placa));
        $placa = str_replace([' ', '–', '—'], '', $placa);
        if (preg_match('/^([A-Z]{3})-?([0-9]{3})$/', $placa, $coincidencias)) {
            return $coincidencias[1] . '-' . $coincidencias[2];
        }

        return $placa;
    }

    public static function mensajes(): array
    {
        return [
            'nombre.regex' => 'El nombre solo puede tener letras.',
            'name.regex' => 'El nombre solo puede tener letras.',
            'cedula.regex' => 'La cédula solo puede tener números.',
            'cedula.required' => 'La cédula es obligatoria.',
            'telefono.regex' => 'El teléfono solo puede tener números.',
            'cargo.regex' => 'El cargo solo puede tener letras.',
            'cargo.required' => 'El cargo es obligatorio.',
            'placa.regex' => 'La placa debe ser tres letras, un guion y tres números. Ejemplo: ABC-123.',
            'placa.required' => 'La placa es obligatoria en esta modalidad.',
            'email.regex' => 'El correo debe ser texto@sipd.co, por ejemplo kellyrodriguez@sipd.co.',
        ];
    }
}

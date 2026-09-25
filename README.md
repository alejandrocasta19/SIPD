# SIPD

Sistema de Recursos Humanos para la administración y seguimiento de procesos disciplinarios.

## Requisitos

- PHP 8.1
- MySQL
- Composer

## Instalación

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

La URL local en Laragon es `http://sipd.test`.

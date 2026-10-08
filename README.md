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
php artisan sipd:create-admin
```

La URL local en Laragon es `http://sipd.test`.

En producción, no ejecutes seeders de demostración. La cuenta inicial de coordinación
se crea con `sipd:create-admin`, que solicita la contraseña de forma interactiva y no
usa credenciales predeterminadas.
Si actualizas una instalación que ya tenía cuentas creadas por versiones anteriores,
cambia cualquier contraseña inicial o compartida antes de abrir el sistema a usuarios.

Antes de publicar, configura como mínimo `APP_ENV=production`, `APP_DEBUG=false`,
`APP_URL` con HTTPS y `SESSION_SECURE_COOKIE=true`. La consulta pública por cédula
continúa sin verificación de identidad, según la decisión funcional del sistema, y se
limita a diez intentos por minuto y dirección IP para reducir la enumeración.
Los botones de acceso rápido solo se muestran con `APP_ENV=local`; sus credenciales
se leen de variables `SIPD_QUICK_*` en el `.env` local y no deben configurarse en
producción.

Los procesos cerrados (`Sancionado` o `Archivado`) se archivan automáticamente el
primer día de cada mes. Sus expedientes y relaciones se conservan en la base de datos
y permanecen incluidos en Reportes; dejan de aparecer en los módulos operativos.
Para activar el programador en Linux, configura el cron de Laravel cada minuto:

```cron
* * * * * cd /ruta/a/SIPD && php artisan schedule:run >> /dev/null 2>&1
```

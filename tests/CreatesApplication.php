<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $storagePath = $this->configureIsolatedTestEnvironment();
        $app = require __DIR__.'/../bootstrap/app.php';
        $app->useStoragePath($storagePath);

        $app->make(Kernel::class)->bootstrap();

        $app['config']->set([
            'database.default' => 'sqlite',
            'database.connections' => [
                'sqlite' => [
                    'driver' => 'sqlite',
                    'url' => null,
                    'database' => ':memory:',
                    'prefix' => '',
                    'foreign_key_constraints' => true,
                ],
            ],
            'database.connections.sqlite.url' => null,
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => true,
            'filesystems.default' => 'local',
            'filesystems.disks' => [
                'local' => [
                    'driver' => 'local',
                    'root' => $storagePath . DIRECTORY_SEPARATOR . 'app',
                ],
                'public' => [
                    'driver' => 'local',
                    'root' => $storagePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'public',
                    'visibility' => 'public',
                ],
            ],
            'filesystems.disks.local.root' => $storagePath . DIRECTORY_SEPARATOR . 'app',
            'filesystems.disks.public.root' => $storagePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'public',
            'logging.default' => 'single',
            'logging.channels' => [
                'single' => [
                    'driver' => 'single',
                    'path' => $storagePath . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'laravel.log',
                    'level' => 'debug',
                ],
                'emergency' => [
                    'path' => $storagePath . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'laravel.log',
                ],
                'null' => [
                    'driver' => 'monolog',
                    'handler' => \Monolog\Handler\NullHandler::class,
                ],
            ],
            'logging.channels.single.path' => $storagePath . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'laravel.log',
            'logging.channels.emergency.path' => $storagePath . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'laravel.log',
            'cache.stores' => [
                'array' => [
                    'driver' => 'array',
                    'serialize' => false,
                ],
            ],
            'session.driver' => 'array',
            'session.files' => $storagePath . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'sessions',
            'cache.default' => 'array',
            'queue.default' => 'sync',
            'queue.connections' => [
                'sync' => ['driver' => 'sync'],
            ],
            'mail.default' => 'array',
            'mail.mailers' => [
                'array' => ['transport' => 'array'],
            ],
            'broadcasting.default' => 'null',
            'broadcasting.connections' => [
                'null' => ['driver' => 'null'],
            ],
            'view.compiled' => $storagePath . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'views',
        ]);

        $this->assertIsolatedTestConfiguration($app, $storagePath);

        return $app;
    }

    private function assertIsolatedTestConfiguration($app, string $storagePath): void
    {
        $expectedStoragePath = realpath($storagePath);
        $configuredStoragePath = realpath($app->storagePath());
        $database = $app['config']->get('database.connections.sqlite');

        if (
            $expectedStoragePath === false
            || $configuredStoragePath !== $expectedStoragePath
            || $app['config']->get('database.default') !== 'sqlite'
            || $database['driver'] !== 'sqlite'
            || $database['url'] !== null
            || $database['database'] !== ':memory:'
            || array_keys($app['config']->get('database.connections')) !== ['sqlite']
            || $app['config']->get('filesystems.default') !== 'local'
            || $app['config']->get('filesystems.disks.local.root') !== $storagePath . DIRECTORY_SEPARATOR . 'app'
            || $app['config']->get('filesystems.disks.public.root') !== $storagePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'public'
            || $app['config']->get('logging.default') !== 'single'
            || strpos($app['config']->get('logging.channels.single.path'), $storagePath . DIRECTORY_SEPARATOR) !== 0
            || $app['config']->get('session.driver') !== 'array'
            || $app['config']->get('cache.default') !== 'array'
            || $app['config']->get('queue.default') !== 'sync'
            || $app['config']->get('mail.default') !== 'array'
            || $app['config']->get('broadcasting.default') !== 'null'
        ) {
            throw new \RuntimeException('La configuración efectiva de pruebas no está aislada.');
        }

        $connection = $app['db']->connection();
        if (
            $connection->getDriverName() !== 'sqlite'
            || $connection->getDatabaseName() !== ':memory:'
            || $connection->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME) !== 'sqlite'
        ) {
            throw new \RuntimeException('La conexión efectiva de pruebas no es SQLite en memoria.');
        }
    }

    private function configureIsolatedTestEnvironment(): string
    {
        $basePath = dirname(__DIR__);
        $storagePath = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR . 'sipd-tests-' . getmypid() . '-' . bin2hex(random_bytes(8));

        $environment = [
            'APP_BASE_PATH' => $basePath,
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
            'DB_FOREIGN_KEYS' => 'true',
            'DATABASE_URL' => '',
            'FILESYSTEM_DRIVER' => 'local',
            'LOG_CHANNEL' => 'single',
            'MAIL_MAILER' => 'array',
            'QUEUE_CONNECTION' => 'sync',
            'SESSION_DRIVER' => 'array',
            'CACHE_DRIVER' => 'array',
            'BROADCAST_DRIVER' => 'log',
            'TELESCOPE_ENABLED' => 'false',
        ];

        foreach ($environment as $key => $value) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        $directories = [
            $storagePath,
            $storagePath . DIRECTORY_SEPARATOR . 'app',
            $storagePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'public',
            $storagePath . DIRECTORY_SEPARATOR . 'logs',
            $storagePath . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'data',
            $storagePath . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'sessions',
            $storagePath . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'testing',
            $storagePath . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'views',
            $storagePath . DIRECTORY_SEPARATOR . 'tmp',
        ];

        foreach ($directories as $directory) {
            if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
                throw new \RuntimeException('No se pudo crear el almacenamiento temporal aislado de pruebas.');
            }
        }

        return $storagePath;
    }
}

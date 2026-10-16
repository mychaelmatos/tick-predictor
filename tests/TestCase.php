<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Impede que a suíte rode com config/rotas em cache.
     *
     * Com `php artisan optimize` ativo, o phpunit.xml é ignorado e os testes
     * (RefreshDatabase) passam a apontar para o banco real do .env.
     * Rode `php artisan optimize:clear` antes de testar.
     */
    protected function setUp(): void
    {
        foreach (['config.php', 'routes-v7.php', 'events.php'] as $cached) {
            if (file_exists(__DIR__.'/../bootstrap/cache/'.$cached)) {
                fwrite(STDERR, "\nABORTADO: bootstrap/cache/{$cached} existe. Rode `php artisan optimize:clear` antes dos testes.\n");
                exit(1);
            }
        }

        parent::setUp();

        // Testes não dependem de `npm run build`: o Blade ignora o manifesto do Vite.
        $this->withoutVite();

        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->fail('Testes devem rodar em SQLite em memória (ver phpunit.xml). Conexão atual: '.config('database.default'));
        }
    }
}

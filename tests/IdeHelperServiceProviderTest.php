<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests;

use Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider as BarryvdhIdeHelperServiceProvider;
use Orchestra\Testbench\TestCase;
use Staudenmeir\LaravelAdjacencyList\IdeHelperServiceProvider;
use Staudenmeir\LaravelAdjacencyList\IdeHelper\RecursiveRelationsHook;

class IdeHelperServiceProviderTest extends TestCase
{
    public function testRegister(): void
    {
        $this->app->loadDeferredProvider(BarryvdhIdeHelperServiceProvider::class);
        $this->app->loadDeferredProvider(IdeHelperServiceProvider::class);

        /** @var \Illuminate\Contracts\Config\Repository $config */
        $config = $this->app->get('config');

        $this->assertContains(
            RecursiveRelationsHook::class,
            $config->get('ide-helper.model_hooks'),
        );
    }

    protected function getPackageProviders($app): array
    {
        return [
            BarryvdhIdeHelperServiceProvider::class,
            IdeHelperServiceProvider::class,
        ];
    }
}

<?php

namespace Staudenmeir\LaravelAdjacencyList;

use Barryvdh\LaravelIdeHelper\Console\ModelsCommand;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Staudenmeir\LaravelAdjacencyList\IdeHelper\RecursiveRelationsHook;

class IdeHelperServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * @var string
     */
    const ModelsCommandAlias = __NAMESPACE__ . '\\' . ModelsCommand::class;

    public function boot(): void
    {
        // Laravel only allows a single deferred service provider to claim
        // responsibility for a given class, interface, or service in the
        // provides() method. To ensure this provider is properly loaded
        // when running the ModelsCommand we bind an alias and use that instead.
        $this->app->alias(ModelsCommand::class, static::ModelsCommandAlias);
    }

    /** @inheritDoc */
    public function register(): void
    {
        /** @var \Illuminate\Config\Repository $config */
        $config = $this->app->get('config');

        $config->set(
            'ide-helper.model_hooks',
            array_merge(
                [RecursiveRelationsHook::class],
                $config->array('ide-helper.model_hooks', [])
            )
        );
    }

    /**
     * @return list<string>
     */
    public function provides(): array
    {
        return [
            static::ModelsCommandAlias,
        ];
    }
}

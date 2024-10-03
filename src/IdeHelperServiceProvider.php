<?php

namespace Staudenmeir\LaravelAdjacencyList;

use Barryvdh\LaravelIdeHelper\Console\ModelsCommand;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Staudenmeir\LaravelAdjacencyList\IdeHelper\RecursiveRelationsHook;

class IdeHelperServiceProvider extends ServiceProvider implements DeferrableProvider
{
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
     * @return list<class-string<\Illuminate\Console\Command>>
     */
    public function provides(): array
    {
        return [
            ModelsCommand::class,
        ];
    }
}

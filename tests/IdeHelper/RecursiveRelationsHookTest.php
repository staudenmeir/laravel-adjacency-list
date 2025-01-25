<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\IdeHelper;

use Barryvdh\LaravelIdeHelper\Console\ModelsCommand;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Staudenmeir\LaravelAdjacencyList\IdeHelper\RecursiveRelationsHook;
use Staudenmeir\LaravelAdjacencyList\Tests\IdeHelper\Models\Node;
use Staudenmeir\LaravelAdjacencyList\Tests\IdeHelper\Models\User;

class RecursiveRelationsHookTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testTreeRelations(): void
    {
        $config = Mockery::mock(Repository::class);
        $config->shouldReceive('get')->andReturn(true);

        $app = Mockery::mock(Application::class);
        $app->shouldReceive('make')->andReturn($config);

        $command = Mockery::mock(ModelsCommand::class);
        $command->shouldReceive('getLaravel')->andReturn($app);
        $command->shouldReceive('setProperty')->times(2);
        $command->shouldReceive('setProperty')->once()->with(
            'ancestorsAndSelf',
            '\Staudenmeir\LaravelAdjacencyList\Eloquent\Collection<int, \Staudenmeir\LaravelAdjacencyList\Tests\IdeHelper\Models\User>',
            true,
            false,
            "The model's recursive parents and itself.",
            false
        );
        $command->shouldReceive('setProperty')->once()->with(
            'ancestors_and_self_count',
            'int',
            true,
            false,
            null,
            true
        );
        $command->shouldReceive('setProperty')->times(10);
        $command->shouldReceive('setProperty')->once()->with(
            'parent',
            '\\Staudenmeir\LaravelAdjacencyList\Tests\IdeHelper\Models\User',
            true,
            false,
            "The model's direct parent.",
            true
        );
        $command->shouldReceive('setProperty')->times(7);

        $hook = new RecursiveRelationsHook();
        $hook->run($command, new User());
    }

    public function testGraphRelations(): void
    {
        $config = Mockery::mock(Repository::class);
        $config->shouldReceive('get')->andReturn(true);

        $app = Mockery::mock(Application::class);
        $app->shouldReceive('make')->andReturn($config);

        $command = Mockery::mock(ModelsCommand::class);
        $command->shouldReceive('getLaravel')->andReturn($app);
        $command->shouldReceive('setProperty')->times(2);
        $command->shouldReceive('setProperty')->once()->with(
            'ancestorsAndSelf',
            '\Staudenmeir\LaravelAdjacencyList\Eloquent\Graph\Collection<int, \Staudenmeir\LaravelAdjacencyList\Tests\IdeHelper\Models\Node>',
            true,
            false,
            "The node's recursive parents and itself.",
            false
        );
        $command->shouldReceive('setProperty')->once()->with(
            'ancestors_and_self_count',
            'int',
            true,
            false,
            null,
            true
        );
        $command->shouldReceive('setProperty')->times(12);

        $hook = new RecursiveRelationsHook();
        $hook->run($command, new Node());
    }
}

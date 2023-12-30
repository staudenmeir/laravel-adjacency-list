<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\IdeHelper;

use Barryvdh\LaravelIdeHelper\Console\ModelsCommand;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Staudenmeir\LaravelAdjacencyList\IdeHelper\RecursiveRelationsHook;
use Staudenmeir\LaravelAdjacencyList\Tests\IdeHelper\Models\Node;
use Staudenmeir\LaravelAdjacencyList\Tests\IdeHelper\Models\User;

class RecursiveRelationsHookTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testTreeRelations()
    {
        $command = Mockery::mock(ModelsCommand::class);
        $command->shouldReceive('setProperty')->times(2);
        $command->shouldReceive('setProperty')->once()->with(
            'ancestorsAndSelf',
            '\Staudenmeir\LaravelAdjacencyList\Eloquent\Collection|\Staudenmeir\LaravelAdjacencyList\Tests\IdeHelper\Models\User[]',
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

    public function testGraphRelations()
    {
        $command = Mockery::mock(ModelsCommand::class);
        $command->shouldReceive('setProperty')->times(2);
        $command->shouldReceive('setProperty')->once()->with(
            'ancestorsAndSelf',
            '\Staudenmeir\LaravelAdjacencyList\Eloquent\Graph\Collection|\Staudenmeir\LaravelAdjacencyList\Tests\IdeHelper\Models\Node[]',
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

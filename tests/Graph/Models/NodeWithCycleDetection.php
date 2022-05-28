<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Graph\Models;

class NodeWithCycleDetection extends Node
{
    protected $table = 'nodes';

    public function enableCycleDetection(): bool
    {
        return true;
    }
}

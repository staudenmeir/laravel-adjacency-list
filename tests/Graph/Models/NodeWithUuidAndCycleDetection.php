<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Graph\Models;

class NodeWithUuidAndCycleDetection extends NodeWithUuid
{
    public function enableCycleDetection(): bool
    {
        return true;
    }
}

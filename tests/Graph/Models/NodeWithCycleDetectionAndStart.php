<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Graph\Models;

class NodeWithCycleDetectionAndStart extends NodeWithCycleDetection
{
    public function includeCycleStart(): bool
    {
        return true;
    }
}

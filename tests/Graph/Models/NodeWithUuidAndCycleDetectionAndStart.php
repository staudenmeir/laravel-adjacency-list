<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Graph\Models;

class NodeWithUuidAndCycleDetectionAndStart extends NodeWithUuidAndCycleDetection
{
    public function includeCycleStart(): bool
    {
        return true;
    }
}

<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models;

class UserWithCycleDetectionAndStart extends UserWithCycleDetection
{
    public function includeCycleStart(): bool
    {
        return true;
    }
}

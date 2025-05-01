<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models;

class UserWithCycleDetection extends User
{
    public function enableCycleDetection(): bool
    {
        return true;
    }
}

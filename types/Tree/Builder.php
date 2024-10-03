<?php

namespace Staudenmeir\LaravelAdjacencyList\Types\Tree;

use Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User;

use function PHPStan\Testing\assertType;

function test(User $user): void
{
    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User>',
        $user->newQuery()
    );
}

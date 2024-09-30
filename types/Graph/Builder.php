<?php

namespace Staudenmeir\LaravelAdjacencyList\Types\Tree;

use Staudenmeir\LaravelAdjacencyList\Types\Graph\Models\Node;

use function PHPStan\Testing\assertType;

function test(Node $node): void
{
    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<Staudenmeir\LaravelAdjacencyList\Types\Graph\Models\Node>',
        $node->newQuery()
    );
}

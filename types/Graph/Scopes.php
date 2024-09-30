<?php

namespace Staudenmeir\LaravelAdjacencyList\Types\Graph;

use Staudenmeir\LaravelAdjacencyList\Types\Graph\Models\Node;

use function PHPStan\Testing\assertType;

function test(Node $node): void
{
    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Graph\Ancestors<Staudenmeir\LaravelAdjacencyList\Types\Graph\Models\Node>',
        $node->ancestors()->subgraph(fn () => null)
    );

    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Graph\Ancestors<Staudenmeir\LaravelAdjacencyList\Types\Graph\Models\Node>',
        $node->ancestors()->whereDepth(3)
    );

    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Graph\Ancestors<Staudenmeir\LaravelAdjacencyList\Types\Graph\Models\Node>',
        $node->ancestors()->breadthFirst()
    );

    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Graph\Ancestors<Staudenmeir\LaravelAdjacencyList\Types\Graph\Models\Node>',
        $node->ancestors()->depthFirst()
    );

    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Graph\Ancestors<Staudenmeir\LaravelAdjacencyList\Types\Graph\Models\Node>',
        $node->ancestors()->whereDepth(3)->breadthFirst()
    );
}

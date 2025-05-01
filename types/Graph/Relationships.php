<?php

namespace Staudenmeir\LaravelAdjacencyList\Types\Graph;

use Staudenmeir\LaravelAdjacencyList\Types\Graph\Models\Node;

use function PHPStan\Testing\assertType;

function test(Node $node): void
{
    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Graph\Ancestors<Staudenmeir\LaravelAdjacencyList\Types\Graph\Models\Node, Staudenmeir\LaravelAdjacencyList\Types\Graph\Models\Node>',
        $node->ancestors()
    );

    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Graph\Ancestors<Staudenmeir\LaravelAdjacencyList\Types\Graph\Models\Node, Staudenmeir\LaravelAdjacencyList\Types\Graph\Models\Node>',
        $node->ancestorsAndSelf()
    );

    assertType(
        "Illuminate\Database\Eloquent\Relations\BelongsToMany<Staudenmeir\LaravelAdjacencyList\Types\Graph\Models\Node, Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'>",
        $node->children()
    );

    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Graph\Descendants<Staudenmeir\LaravelAdjacencyList\Types\Graph\Models\Node, Staudenmeir\LaravelAdjacencyList\Types\Graph\Models\Node>',
        $node->childrenAndSelf()
    );

    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Graph\Descendants<Staudenmeir\LaravelAdjacencyList\Types\Graph\Models\Node, Staudenmeir\LaravelAdjacencyList\Types\Graph\Models\Node>',
        $node->descendants()
    );

    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Graph\Descendants<Staudenmeir\LaravelAdjacencyList\Types\Graph\Models\Node, Staudenmeir\LaravelAdjacencyList\Types\Graph\Models\Node>',
        $node->descendantsAndSelf()
    );

    assertType(
        "Illuminate\Database\Eloquent\Relations\BelongsToMany<Staudenmeir\LaravelAdjacencyList\Types\Graph\Models\Node, Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'>",
        $node->parents()
    );

    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Graph\Ancestors<Staudenmeir\LaravelAdjacencyList\Types\Graph\Models\Node, Staudenmeir\LaravelAdjacencyList\Types\Graph\Models\Node>',
        $node->parentsAndSelf()
    );
}

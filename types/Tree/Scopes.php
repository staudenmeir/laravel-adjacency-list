<?php

namespace Staudenmeir\LaravelAdjacencyList\Types\Tree;

use Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User;

use function PHPStan\Testing\assertType;

function test(User $user): void
{
    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User>',
        $user->tree()
    );

    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User>',
        $user->treeOf(fn () => null)
    );

    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Ancestors<Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User>',
        $user->ancestors()->doesntHaveChildren()
    );

    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Ancestors<Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User>',
        $user->ancestors()->hasChildren()
    );

    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Ancestors<Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User>',
        $user->ancestors()->hasParent()
    );

    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Ancestors<Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User>',
        $user->ancestors()->isLeaf()
    );

    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Ancestors<Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User>',
        $user->ancestors()->isRoot()
    );

    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Ancestors<Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User>',
        $user->ancestors()->breadthFirst()
    );

    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Ancestors<Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User>',
        $user->ancestors()->depthFirst()
    );

    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Ancestors<Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User>',
        $user->ancestors()->hasChildren()->breadthFirst()
    );
}

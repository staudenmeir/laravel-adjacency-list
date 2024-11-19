<?php

namespace Staudenmeir\LaravelAdjacencyList\Types\Tree;

use Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\Post;
use Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\Role;
use Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\Tag;
use Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User;

use function PHPStan\Testing\assertType;

function test(User $user): void
{
    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Ancestors<Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User, Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User>',
        $user->ancestors()
    );

    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Ancestors<Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User, Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User>',
        $user->ancestorsAndSelf()
    );

    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Bloodline<Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User, Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User>',
        $user->bloodline()
    );

    assertType(
        'Illuminate\Database\Eloquent\Relations\HasMany<Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User, Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User>',
        $user->children()
    );

    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Descendants<Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User, Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User>',
        $user->childrenAndSelf()
    );

    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Descendants<Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User, Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User>',
        $user->descendants()
    );

    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Descendants<Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User, Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User>',
        $user->descendantsAndSelf()
    );

    assertType(
        'Illuminate\Database\Eloquent\Relations\BelongsTo<Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User, Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User>',
        $user->parent()
    );

    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Ancestors<Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User, Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User>',
        $user->parentAndSelf()
    );

    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\RootAncestor<Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User, Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User>',
        $user->rootAncestor()
    );

    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\RootAncestorOrSelf<Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User, Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User>',
        $user->rootAncestorOrSelf()
    );

    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Siblings<Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User, Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User>',
        $user->siblings()
    );

    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Siblings<Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User, Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User>',
        $user->siblingsAndSelf()
    );

    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\BelongsToManyOfDescendants<Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\Role, Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User>',
        $user->belongsToManyOfDescendants(Role::class)
    );

    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\BelongsToManyOfDescendants<Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\Role, Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User>',
        $user->belongsToManyOfDescendantsAndSelf(Role::class)
    );

    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\HasManyOfDescendants<Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\Post, Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User>',
        $user->hasManyOfDescendantsAndSelf(Post::class)
    );

    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\HasManyOfDescendants<Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\Post, Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User>',
        $user->hasManyOfDescendantsAndSelf(Post::class)
    );

    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\MorphToManyOfDescendants<Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\Tag, Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User>',
        $user->morphToManyOfDescendants(Tag::class, 'taggable')
    );

    assertType(
        'Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\MorphToManyOfDescendants<Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\Tag, Staudenmeir\LaravelAdjacencyList\Types\Tree\Models\User>',
        $user->morphToManyOfDescendantsAndSelf(Tag::class, 'taggable')
    );
}

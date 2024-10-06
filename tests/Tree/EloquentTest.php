<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Tree;

use Illuminate\Database\Eloquent\Builder;
use Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models\Category;
use Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models\User;

class EloquentTest extends TestCase
{
    public function testChildren(): void
    {
        $children = User::find(1)->children()->orderBy('id')->get();

        $this->assertEquals([2, 3, 4], $children->pluck('id')->all());
    }

    public function testChildrenAndSelf(): void
    {
        $childrenAndSelf = User::find(1)->childrenAndSelf()->orderBy('id')->get();

        $this->assertEquals([1, 2, 3, 4], $childrenAndSelf->pluck('id')->all());
    }

    public function testParent(): void
    {
        $parent = User::find(8)->parent;

        $this->assertEquals(5, $parent->id);
    }

    public function testParentAndSelf(): void
    {
        $parentAndSelf = User::find(8)->parentAndSelf()->depthFirst()->get();

        $this->assertEquals([8, 5], $parentAndSelf->pluck('id')->all());
    }

    public function testScopeTree(): void
    {
        $users = User::tree()->orderBy('id')->get();

        $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9, 11, 12], $users->pluck('id')->all());
        $this->assertEquals([0, 1, 1, 1, 2, 2, 2, 3, 3, 0, 1], $users->pluck('depth')->all());
        $this->assertEquals(
            ['1', '1.2', '1.3', '1.4', '1.2.5', '1.3.6', '1.4.7', '1.2.5.8', '1.3.6.9', '11', '11.12'],
            $users->pluck('path')->all()
        );
        $this->assertEquals(
            ['user-1', 'user-1/user-2', 'user-1/user-3'],
            $users->pluck('slug_path')->slice(0, 3)->all()
        );
        $this->assertEquals(
            ['user-1', 'user-2/user-1', 'user-3/user-1'],
            $users->pluck('reverse_slug_path')->slice(0, 3)->all()
        );
        $this->assertEquals('users', $users[0]->getTable());
    }

    public function testScopeTreeWithMaxDepth(): void
    {
        $tree = User::tree(2)->orderBy('id')->get();

        $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 11, 12], $tree->pluck('id')->all());
    }

    public function testScopeTreeOfWithCallable(): void
    {
        $constraint = fn (Builder $query) => $query->whereIn('id', [2, 4]);

        $tree = User::treeOf($constraint)->orderBy('id')->get();

        $this->assertEquals([2, 4, 5, 7, 8], $tree->pluck('id')->all());
    }

    public function testScopeTreeOfWithCallableAndMaxDepth(): void
    {
        $constraint = fn (Builder $query) => $query->whereIn('id', [2, 4]);

        $tree = User::treeOf($constraint, 1)->orderBy('id')->get();

        $this->assertEquals([2, 4, 5, 7], $tree->pluck('id')->all());
    }

    public function testScopeTreeOfWithModel(): void
    {
        $model = User::find(2);

        $tree = User::treeOf($model)->orderBy('id')->get();

        $this->assertEquals([2, 5, 8], $tree->pluck('id')->all());
    }

    public function testScopeTreeOfWithModelAndMaxDepth(): void
    {
        $model = User::find(2);

        $tree = User::treeOf($model, 1)->orderBy('id')->get();

        $this->assertEquals([2, 5], $tree->pluck('id')->all());
    }

    public function testScopeHasChildren(): void
    {
        $users = User::hasChildren()->orderBy('id')->get();

        $this->assertEquals([1, 2, 3, 4, 5, 6, 11], $users->pluck('id')->all());
    }

    public function testScopeDoesntHaveChildren(): void
    {
        $users = User::doesntHaveChildren()->orderBy('id')->get();

        $this->assertEquals([7, 8, 9, 12], $users->pluck('id')->all());
    }

    public function testScopeHasParent(): void
    {
        $users = User::hasParent()->orderBy('id')->get();

        $this->assertEquals([2, 3, 4, 5, 6, 7, 8, 9, 12], $users->pluck('id')->all());
    }

    public function testScopeIsLeaf(): void
    {
        $users = User::isLeaf()->orderBy('id')->get();

        $this->assertEquals([7, 8, 9, 12], $users->pluck('id')->all());
    }

    public function testScopeIsRoot(): void
    {
        $users = User::isRoot()->orderBy('id')->get();

        $this->assertEquals([1, 11], $users->pluck('id')->all());
    }

    public function testScopeWhereDepth(): void
    {
        $users = User::find(1)->descendants()->whereDepth(1)->orderBy('id')->get();

        $this->assertEquals([2, 3, 4], $users->pluck('id')->all());
    }

    public function testScopeWhereDepthWithOperator(): void
    {
        $users = User::find(1)->descendants()->whereDepth('>', 2)->orderBy('id')->get();

        $this->assertEquals([8, 9], $users->pluck('id')->all());
    }

    public function testScopeBreadthFirst(): void
    {
        $users = User::tree()->breadthFirst()->orderByDesc('id')->get();

        $this->assertEquals([11, 1, 12, 4, 3, 2, 7, 6, 5, 9, 8], $users->pluck('id')->all());
    }

    public function testScopeDepthFirst(): void
    {
        if ($this->connection === 'firebird') {
            $this->markTestSkipped();
        }

        $users = User::tree()->depthFirst()->get();

        $this->assertEquals([1, 2, 5, 8, 3, 6, 9, 4, 7, 11, 12], $users->pluck('id')->all());
    }

    public function testScopeDepthFirstWithNaturalSorting(): void
    {
        if (in_array($this->connection, ['sqlite', 'sqlsrv', 'singlestore', 'firebird'])) {
            $this->markTestSkipped();
        }

        User::forceCreate(['id' => 70, 'slug' => 'user-70', 'parent_id' => 5, 'followers' => 1, 'deleted_at' => null]);

        $users = User::tree()->depthFirst()->get();

        $this->assertEquals([1, 2, 5, 8, 70, 3, 6, 9, 4, 7, 11, 12], $users->pluck('id')->all());
    }

    public function testScopeDepthFirstWithStringKey(): void
    {
        if ($this->connection === 'firebird') {
            $this->markTestSkipped();
        }

        $categories = Category::tree()->depthFirst()->get();

        $this->assertEquals(['a', 'b', 'c', 'd'], $categories->pluck('id')->all());
    }

    public function testIsChildOf(): void
    {
        $this->assertTrue(User::find(5)->isChildOf(User::find(2)));
        $this->assertFalse(User::find(5)->isChildOf(User::find(1)));
        $this->assertFalse(User::find(1)->isChildOf(User::find(2)));
    }

    public function testIsParentOf(): void
    {
        $this->assertTrue(User::find(2)->isParentOf(User::find(5)));
        $this->assertFalse(User::find(1)->isParentOf(User::find(5)));
        $this->assertFalse(User::find(2)->isParentOf(User::find(1)));
    }

    public function testGetDepthRelatedTo(): void
    {
        $this->assertEquals(2, User::find(5)->getDepthRelatedTo(User::find(1)));
        $this->assertEquals(-2, User::find(1)->getDepthRelatedTo(User::find(5)));
        $this->assertNull(User::find(4)->getDepthRelatedTo(User::find(5)));
    }

    public function testWithInitialQueryConstraint(): void
    {
        $users = User::withInitialQueryConstraint(function (Builder $query) {
            $query->where('users.id', '<>', 1);
        }, function () {
            return User::tree()->orderBy('id')->get();
        });

        $this->assertEquals([11, 12], $users->pluck('id')->all());

        $users = User::tree()->orderBy('id')->get();

        $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9, 11, 12], $users->pluck('id')->all());
    }

    public function testWithRecursiveQueryConstraint(): void
    {
        $users = User::withRecursiveQueryConstraint(function (Builder $query) {
            $query->where('users.id', '<', 5);
        }, function () {
            return User::tree()->orderBy('id')->get();
        });

        $this->assertEquals([1, 2, 3, 4, 11], $users->pluck('id')->all());

        $users = User::tree()->orderBy('id')->get();

        $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9, 11, 12], $users->pluck('id')->all());
    }

    public function testSetRecursiveQueryConstraint(): void
    {
        User::setRecursiveQueryConstraint(
            fn (Builder $query) => $query->where('users.id', '<', 5)
        );

        $users = User::tree()->orderBy('id')->get();

        $this->assertEquals([1, 2, 3, 4, 11], $users->pluck('id')->all());

        User::unsetRecursiveQueryConstraint();

        $users = User::tree()->orderBy('id')->get();

        $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9, 11, 12], $users->pluck('id')->all());
    }

    public function testWithQueryConstraint(): void
    {
        $users = User::withQueryConstraint(
            fn (Builder $query) => $query->where('users.id', '<', 5),
            fn () => User::tree()->orderBy('id')->get()
        );

        $this->assertEquals([1, 2, 3, 4], $users->pluck('id')->all());

        $users = User::tree()->orderBy('id')->get();

        $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9, 11, 12], $users->pluck('id')->all());
    }

    public function testWithMaxDepth(): void
    {
        $users = User::withMaxDepth(
            2,
            fn () => User::find(1)->descendants()->orderBy('id')->get()
        );

        $this->assertEquals([2, 3, 4, 5, 6, 7], $users->pluck('id')->all());
    }

    public function testWithMaxDepthWithNegativeDepth(): void
    {
        $users = User::withMaxDepth(
            -2,
            fn () => User::find(8)->ancestors()->orderBy('id')->get()
        );

        $this->assertEquals([2, 5], $users->pluck('id')->all());
    }

    public function testLazyLoadingWithMultipleScopes(): void
    {
        $user = User::find(8);

        $ancestorsAndSelf = $user->ancestorAndSelfWithMultipleScopes()->get();

        $this->assertEquals([2, 5, 8], $ancestorsAndSelf->pluck('id')->all());
    }

    public function testEagerLoadingWithMultipleScopes(): void
    {
        $user = User::find(8);

        $ancestorsAndSelf = $user->ancestorAndSelfWithMultipleScopes;

        $this->assertEquals([2, 5, 8], $ancestorsAndSelf->pluck('id')->all());
    }
}

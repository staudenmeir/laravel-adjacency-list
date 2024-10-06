<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Tree;

use Illuminate\Support\Facades\DB;
use Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models\User;

class CollectionTest extends TestCase
{
    public function testToTree()
    {
        $users = User::tree()->orderBy('id')->get();

        $tree = $users->toTree();

        $this->assertEquals([1, 11], $tree->pluck('id')->all());
        $this->assertEquals([2, 3, 4], $tree[0]->children->pluck('id')->all());
        $this->assertEquals([5], $tree[0]->children[0]->children->pluck('id')->all());
        $this->assertEquals([8], $tree[0]->children[0]->children[0]->children->pluck('id')->all());
        $this->assertEquals([12], $tree[1]->children->pluck('id')->all());
    }

    public function testToTreeWithRelationship()
    {
        $users = User::find(1)->descendants()->orderBy('id')->get();

        $tree = $users->toTree();

        $this->assertEquals([2, 3, 4], $tree->pluck('id')->all());
        $this->assertEquals([5], $tree[0]->children->pluck('id')->all());
        $this->assertEquals([8], $tree[0]->children[0]->children->pluck('id')->all());
    }

    public function testToTreeWithEmptyCollection()
    {
        $users = User::tree(1)->where('id', 0)->get();

        $tree = $users->toTree();

        $this->assertEmpty($tree);
    }

    public function testLoadTreePathRelations(): void
    {
        DB::enableQueryLog();

        $tree = User::tree()->get()->loadTreePathRelations();

        $this->assertCount(1, DB::getQueryLog());

        foreach ($tree as $user) {
            $this->assertTrue($user->relationLoaded('ancestors'));
            $this->assertTrue($user->relationLoaded('ancestorsAndSelf'));
            $this->assertTrue($user->relationLoaded('parent'));

            $this->assertEquals($user->ancestors()->pluck('id')->all(), $user->ancestors->pluck('id')->all());
            $this->assertEquals($user->ancestorsAndSelf()->pluck('id')->all(), $user->ancestorsAndSelf->pluck('id')->all());
            $this->assertEquals($user->parent()->first()?->id, $user->parent?->id);
        }
    }

    public function testLoadTreePathRelationsWithMissingModels(): void
    {
        DB::enableQueryLog();

        $tree = User::tree()->where('id', '>', 5)->get()->loadTreePathRelations();

        $this->assertCount(2, DB::getQueryLog());

        foreach ($tree as $user) {
            $this->assertTrue($user->relationLoaded('ancestors'));
            $this->assertTrue($user->relationLoaded('ancestorsAndSelf'));
            $this->assertTrue($user->relationLoaded('parent'));

            $this->assertEquals($user->ancestors()->pluck('id')->all(), $user->ancestors->pluck('id')->all());
            $this->assertEquals($user->ancestorsAndSelf()->pluck('id')->all(), $user->ancestorsAndSelf->pluck('id')->all());
            $this->assertEquals($user->parent()->first()?->id, $user->parent?->id);
        }
    }

    public function testLoadTreePathRelationsWithEmptyCollection(): void
    {
        $users = User::tree(1)->where('id', 0)->get();

        $tree = $users->toTree()->loadTreePathRelations();

        $this->assertEmpty($tree);
    }
}

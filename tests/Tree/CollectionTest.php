<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Tree;

use Illuminate\Support\Facades\DB;
use Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models\User;

class CollectionTest extends TestCase
{
    public function testToTree(): void
    {
        $users = User::tree()->orderBy('id')->get();

        $tree = $users->toTree();

        $this->assertEquals([1, 11], $tree->pluck('id')->all());
        $this->assertEquals([2, 3, 4], $tree[0]->children->pluck('id')->all());
        $this->assertEquals([5], $tree[0]->children[0]->children->pluck('id')->all());
        $this->assertEquals([8], $tree[0]->children[0]->children[0]->children->pluck('id')->all());
        $this->assertEquals([12], $tree[1]->children->pluck('id')->all());
    }

    public function testToTreeWithRelationship(): void
    {
        $users = User::find(1)->descendants()->orderBy('id')->get();

        $tree = $users->toTree();

        $this->assertEquals([2, 3, 4], $tree->pluck('id')->all());
        $this->assertEquals([5], $tree[0]->children->pluck('id')->all());
        $this->assertEquals([8], $tree[0]->children[0]->children->pluck('id')->all());
    }

    public function testToTreeWithEmptyCollection(): void
    {
        $users = User::tree(1)->where('id', 0)->get();

        $tree = $users->toTree();

        $this->assertEmpty($tree);
    }

    public function testLoadTreeRelationships(): void
    {
        DB::enableQueryLog();

        $users = User::tree()->get()->loadTreeRelationships();

        $this->assertCount(1, DB::getQueryLog());

        foreach ($users as $user) {
            $this->assertTrue($user->relationLoaded('ancestors'));
            $this->assertTrue($user->relationLoaded('ancestorsAndSelf'));
            $this->assertTrue($user->relationLoaded('parent'));

            $this->assertEquals($user->ancestors()->orderByDesc('depth')->pluck('id')->all(), $user->ancestors->pluck('id')->all());
            $this->assertEquals($user->ancestorsAndSelf()->orderByDesc('depth')->pluck('id')->all(), $user->ancestorsAndSelf->pluck('id')->all());
            $this->assertEquals($user->parent()->first()?->id, $user->parent?->id);
        }
    }

    public function testLoadTreeRelationshipsWithMissingModels(): void
    {
        DB::enableQueryLog();

        $users = User::tree()->where('id', '>', 5)->get()->loadTreeRelationships();

        $this->assertCount(2, DB::getQueryLog());

        foreach ($users as $user) {
            $this->assertTrue($user->relationLoaded('ancestors'));
            $this->assertTrue($user->relationLoaded('ancestorsAndSelf'));
            $this->assertTrue($user->relationLoaded('parent'));

            $this->assertEquals($user->ancestors()->orderByDesc('depth')->pluck('id')->all(), $user->ancestors->pluck('id')->all());
            $this->assertEquals($user->ancestorsAndSelf()->orderByDesc('depth')->pluck('id')->all(), $user->ancestorsAndSelf->pluck('id')->all());
            $this->assertEquals($user->parent()->first()?->id, $user->parent?->id);
        }
    }

    public function testLoadTreeRelationshipsWithEmptyCollection(): void
    {
        $users = User::tree(1)->where('id', 0)->get()->loadTreeRelationships();

        $this->assertEmpty($users);
    }
}

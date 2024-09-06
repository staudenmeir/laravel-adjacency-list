<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Tree;

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

    public function testLoadTreePathRelations()
    {
        $limit = User::count();

        $loaded = 0;

        User::retrieved(function () use (&$count) {
            $count++;
        });
        
        $tree = User::query()
            ->tree()
            ->get()
            ->loadTreePathRelations()
            ->each(fn ($s) => $s->setAppends(['display_path', 'reverse_display_path']))
            ->toTree();

        $this->assertLessThanOrEqual($limit, $loaded);

        $this->assertEquals('user-1', $tree[0]->display_path);
        $this->assertEquals('user-11', $tree[1]->display_path);
        $this->assertEquals('user-1 > user-2', $tree[0]->children[0]->display_path);
        $this->assertEquals('user-1 > user-3', $tree[0]->children[1]->display_path);
        $this->assertEquals('user-1 > user-4', $tree[0]->children[2]->display_path);
        $this->assertEquals('user-1 > user-2 > user-5', $tree[0]->children[0]->children[0]->display_path);
        $this->assertEquals('user-1 > user-2 > user-5 > user-8', $tree[0]->children[0]->children[0]->children[0]->display_path);
        $this->assertEquals('user-11 > user-12', $tree[1]->children[0]->display_path);
    }
}

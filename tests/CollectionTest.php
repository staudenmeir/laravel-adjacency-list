<?php

namespace Tests;

use Tests\Models\User;

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

    public function testToTreeWithDescendants()
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
}

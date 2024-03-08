<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Graph\Concatenation;

use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\LaravelAdjacencyList\Tests\Graph\Models\Node;
use Staudenmeir\LaravelAdjacencyList\Tests\Graph\Models\Post;
use Staudenmeir\LaravelAdjacencyList\Tests\Graph\TestCase;

class AncestorsTest extends TestCase
{
    public function testLazyLoading()
    {
        $posts = Node::find(2)->ancestorPosts()->orderBy('id')->get();

        $this->assertEquals([101, 109], $posts->pluck('id')->all());
    }

    public function testLazyLoadingAndSelf()
    {
        if (in_array($this->connection, ['sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $posts = Node::find(2)->ancestorAndSelfPosts()->orderBy('id')->get();

        $this->assertEquals([101, 102, 109], $posts->pluck('id')->all());
    }

    public function testEagerLoading()
    {
        $nodes = Node::with([
            'ancestorPosts' => fn (HasManyDeep $query) => $query->orderBy('id'),
        ])->get();

        $this->assertEquals([], $nodes[0]->ancestorPosts->pluck('id')->all());
        $this->assertEquals([101, 109], $nodes[1]->ancestorPosts->pluck('id')->all());
        $this->assertEquals([101, 101, 102, 109], $nodes[4]->ancestorPosts->pluck('id')->all());
    }

    public function testEagerLoadingAndSelf()
    {
        if (in_array($this->connection, ['sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $nodes = Node::with([
            'ancestorAndSelfPosts' => fn (HasManyDeep $query) => $query->orderBy('id'),
        ])->get();

        $this->assertEquals([101], $nodes[0]->ancestorAndSelfPosts->pluck('id')->all());
        $this->assertEquals([101, 102, 109], $nodes[1]->ancestorAndSelfPosts->pluck('id')->all());
        $this->assertEquals([101, 101, 102, 105, 109], $nodes[4]->ancestorAndSelfPosts->pluck('id')->all());
    }

    public function testEagerLoadingWithHasOneDeep()
    {
        $nodes = Node::with([
            'ancestorPost' => fn (HasManyDeep $query) => $query->orderBy('id'),
        ])->orderBy('id')->get();

        $this->assertNull($nodes[0]->ancestorPost);
        $this->assertEquals(101, $nodes[1]->ancestorPost->id);
    }

    public function testLazyEagerLoading()
    {
        $nodes = Node::all()->load([
            'ancestorPosts' => fn (HasManyDeep $query) => $query->orderBy('id'),
        ]);

        $this->assertEquals([], $nodes[0]->ancestorPosts->pluck('id')->all());
        $this->assertEquals([101, 109], $nodes[1]->ancestorPosts->pluck('id')->all());
        $this->assertEquals([101, 101, 102, 109], $nodes[4]->ancestorPosts->pluck('id')->all());
    }

    public function testLazyEagerLoadingAndSelf()
    {
        if (in_array($this->connection, ['sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $nodes = Node::all()->load([
            'ancestorAndSelfPosts' => fn (HasManyDeep $query) => $query->orderBy('id'),
        ]);

        $this->assertEquals([101], $nodes[0]->ancestorAndSelfPosts->pluck('id')->all());
        $this->assertEquals([101, 102, 109], $nodes[1]->ancestorAndSelfPosts->pluck('id')->all());
        $this->assertEquals([101, 101, 102, 105, 109], $nodes[4]->ancestorAndSelfPosts->pluck('id')->all());
    }

    public function testExistenceQuery()
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $nodes = Node::find(1)->descendants()->has('ancestorPosts', '=', 2)->get();

        $this->assertEquals([2, 6], $nodes->pluck('id')->all());
    }

    public function testExistenceQueryAndSelf()
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $nodes = Node::find(1)->descendants()->has('ancestorAndSelfPosts', '=', 3)->get();

        $this->assertEquals([2, 6], $nodes->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelation()
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $nodes = Node::has('ancestorPosts', '=', 2)->get();

        $this->assertEquals([2, 6], $nodes->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelationAndSelf()
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $nodes = Node::has('ancestorAndSelfPosts', '=', 3)->get();

        $this->assertEquals([2, 6], $nodes->pluck('id')->all());
    }

    public function testUnsupportedPosition()
    {
        $this->expectExceptionMessage('Ancestors can only be at the beginning of deep relationships at the moment.');

        Post::find(101)->nodeAncestors;
    }
}

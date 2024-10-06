<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Graph\Concatenation;

use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\LaravelAdjacencyList\Tests\Graph\Models\Node;
use Staudenmeir\LaravelAdjacencyList\Tests\Graph\Models\Post;
use Staudenmeir\LaravelAdjacencyList\Tests\Graph\TestCase;

class DescendantsTest extends TestCase
{
    public function testLazyLoading(): void
    {
        $posts = Node::find(2)->descendantPosts()->orderBy('id')->get();

        $this->assertEquals([105, 107, 108, 108], $posts->pluck('id')->all());
    }

    public function testLazyLoadingAndSelf(): void
    {
        if (in_array($this->connection, ['sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $posts = Node::find(2)->descendantAndSelfPosts()->orderBy('id')->get();

        $this->assertEquals([102, 105, 107, 108, 108], $posts->pluck('id')->all());
    }

    public function testEagerLoading(): void
    {
        $nodes = Node::with([
            'descendantPosts' => fn (HasManyDeep $query) => $query->orderBy('id'),
        ])->get();

        $this->assertEquals([105, 107, 108, 108], $nodes[1]->descendantPosts->pluck('id')->all());
        $this->assertEquals([106], $nodes[2]->descendantPosts->pluck('id')->all());
    }

    public function testEagerLoadingAndSelf(): void
    {
        if (in_array($this->connection, ['sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $nodes = Node::with([
            'descendantAndSelfPosts' => fn (HasManyDeep $query) => $query->orderBy('id'),
        ])->get();

        $this->assertEquals([102, 105, 107, 108, 108], $nodes[1]->descendantAndSelfPosts->pluck('id')->all());
        $this->assertEquals([103, 106], $nodes[2]->descendantAndSelfPosts->pluck('id')->all());
    }

    public function testEagerLoadingWithHasOneDeep(): void
    {
        $nodes = Node::with([
            'descendantPost' => fn (HasManyDeep $query) => $query->orderBy('id'),
        ])->orderBy('id')->get();

        $this->assertEquals(102, $nodes[0]->descendantPost->id);
        $this->assertNull($nodes[5]->descendantPost);
    }

    public function testLazyEagerLoading(): void
    {
        $nodes = Node::all()->load([
            'descendantPosts' => fn (HasManyDeep $query) => $query->orderBy('id'),
        ]);

        $this->assertEquals([105, 107, 108, 108], $nodes[1]->descendantPosts->pluck('id')->all());
        $this->assertEquals([106], $nodes[2]->descendantPosts->pluck('id')->all());
    }

    public function testLazyEagerLoadingAndSelf(): void
    {
        if (in_array($this->connection, ['sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $nodes = Node::all()->load([
            'descendantAndSelfPosts' => fn (HasManyDeep $query) => $query->orderBy('id'),
        ]);

        $this->assertEquals([102, 105, 107, 108, 108], $nodes[1]->descendantAndSelfPosts->pluck('id')->all());
        $this->assertEquals([103, 106], $nodes[2]->descendantAndSelfPosts->pluck('id')->all());
    }

    public function testExistenceQuery(): void
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $nodes = Node::find(5)->ancestors()->has('descendantPosts', '>', 4)->get();

        $this->assertEquals([1, 1, 9], $nodes->pluck('id')->all());
    }

    public function testExistenceQueryAndSelf(): void
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $nodes = Node::find(5)->ancestors()->has('descendantAndSelfPosts', '>', 4)->get();

        $this->assertEquals([1, 2, 1, 9], $nodes->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelation(): void
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $nodes = Node::has('descendantPosts', '>', 4)->get();

        $this->assertEquals([1, 9], $nodes->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelationAndSelf(): void
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $nodes = Node::has('descendantAndSelfPosts', '>', 4)->get();

        $this->assertEquals([1, 2, 9], $nodes->pluck('id')->all());
    }

    public function testUnsupportedPosition(): void
    {
        $this->expectExceptionMessage('Descendants can only be at the beginning of deep relationships at the moment.');

        Post::find(101)->nodeDescendants;
    }
}

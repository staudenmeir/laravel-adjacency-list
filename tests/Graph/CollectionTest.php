<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Graph;

use Illuminate\Database\Eloquent\Builder;
use Staudenmeir\LaravelAdjacencyList\Tests\Graph\Models\Node;
use Staudenmeir\LaravelAdjacencyList\Tests\Graph\Models\NodeWithCycleDetection;
use Staudenmeir\LaravelAdjacencyList\Tests\Graph\Models\NodeWithCycleDetectionAndStart;

class CollectionTest extends TestCase
{
    public function testToTree()
    {
        if (in_array($this->connection, ['sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $constraint = fn (Builder $query) => $query->whereIn('id', [2, 3]);

        $nodes = Node::subgraph($constraint)->orderBy('id')->get();

        $graph = $nodes->toTree();

        $this->assertEquals([2, 3], $graph->pluck('id')->all());
        $this->assertEquals([5], $graph[0]->children->pluck('id')->all());
        $this->assertEquals([7, 8], $graph[0]->children[0]->children->pluck('id')->all());
        $this->assertEquals([8], $graph[0]->children[0]->children[0]->children->pluck('id')->all());
        $this->assertEquals([6], $graph[1]->children->pluck('id')->all());
    }

    public function testToTreeWithRelationship()
    {
        $nodes = Node::find(2)->descendants()->orderBy('id')->get();

        $graph = $nodes->toTree();

        $this->assertEquals([5], $graph->pluck('id')->all());
        $this->assertEquals([7, 8], $graph[0]->children->pluck('id')->all());
        $this->assertEquals([8], $graph[0]->children[0]->children->pluck('id')->all());
    }

    public function testToTreeWithCycle()
    {
        if (in_array($this->connection, ['sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $this->seedCycle();

        $constraint = fn (Builder $query) => $query->where('id', 12);

        $nodes = NodeWithCycleDetection::subgraph($constraint)->orderBy('id')->get();

        $graph = $nodes->toTree();

        $this->assertEquals([12], $graph->pluck('id')->all());
        $this->assertEquals([13], $graph[0]->children->pluck('id')->all());
        $this->assertEquals([14], $graph[0]->children[0]->children->pluck('id')->all());
        $this->assertEmpty($graph[0]->children[0]->children[0]->children);
    }

    public function testToTreeWithCycleAndStart()
    {
        if (in_array($this->connection, ['sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $this->seedCycle();

        $constraint = fn (Builder $query) => $query->where('id', 12);

        $nodes = NodeWithCycleDetectionAndStart::subgraph($constraint)->orderBy('id')->get();

        $graph = $nodes->toTree();

        $this->assertEquals([12], $graph->pluck('id')->all());
        $this->assertEquals([13], $graph[0]->children->pluck('id')->all());
        $this->assertEquals([14], $graph[0]->children[0]->children->pluck('id')->all());
        $this->assertEquals([12], $graph[0]->children[0]->children[0]->children->pluck('id')->all());
    }

    public function testToTreeWithEmptyCollection()
    {
        if (in_array($this->connection, ['sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $constraint = fn (Builder $query) => $query->where('id', 1);

        $nodes = Node::subgraph($constraint)->where('id', 0)->orderBy('id')->get();

        $graph = $nodes->toTree();

        $this->assertEmpty($graph);
    }
}

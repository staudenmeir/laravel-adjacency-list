<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Graph;

use Illuminate\Database\Eloquent\Builder;
use Staudenmeir\LaravelAdjacencyList\Tests\Graph\Models\Node;

class EloquentTest extends TestCase
{
    public function testScopeSubgraph()
    {
        $constraint = function (Builder $query) {
            $query->whereIn('id', [34, 54]);
        };

        $graph = Node::subgraph($constraint)->orderBy('id')->get();

        $this->assertEquals([34, 54, 64, 74, 84, 84], $graph->pluck('id')->all());
    }

    public function testScopeSubgraphWithMaxDepth()
    {
        $constraint = function (Builder $query) {
            $query->whereIn('id', [34, 54]);
        };

        $graph = Node::subgraph($constraint, 1)->orderBy('id')->get();

        $this->assertEquals([34, 54, 64, 74, 84], $graph->pluck('id')->all());
    }

    public function testChildren()
    {
        $children = Node::find(14)->children;

        $this->assertEquals([24, 34, 44, 54], $children->pluck('id')->all());
    }

    public function testChildrenAndSelf()
    {
        $childrenAndSelf = Node::find(14)->childrenAndSelf;

        $this->assertEquals([14, 24, 34, 44, 54], $childrenAndSelf->pluck('id')->all());
    }

    public function testParents()
    {
        $parents = Node::find(54)->parents;

        $this->assertEquals([14, 24, 104], $parents->pluck('id')->all());
    }

    public function testParentsAndSelf()
    {
        $parentsAndSelf = Node::find(54)->parentsAndSelf()->orderByDesc('depth')->orderBy('id')->get();

        $this->assertEquals([54, 14, 24, 104], $parentsAndSelf->pluck('id')->all());
    }

    public function testScopeWhereDepth()
    {
        $nodes = Node::find(14)->descendants()->whereDepth(1)->get();

        $this->assertEquals([24, 34, 44, 54], $nodes->pluck('id')->all());
    }

    public function testScopeWhereDepthWithOperator()
    {
        $nodes = Node::find(14)->descendants()->whereDepth('>', 2)->orderBy('id')->get();

        $this->assertEquals([74, 84, 84, 84], $nodes->pluck('id')->all());
    }

    public function testScopeBreadthFirst()
    {
        $nodes = Node::find(14)->descendants()->breadthFirst()->orderByDesc('id')->get();

        $this->assertEquals([54, 44, 34, 24, 84, 74, 64, 54, 84, 84, 74, 84], $nodes->pluck('id')->all());
    }

    public function testScopeDepthFirst()
    {
        $nodes = Node::find(14)->descendants()->depthFirst()->get();

        $this->assertEquals([24, 54, 74, 84, 84, 34, 64, 44, 54, 74, 84, 84], $nodes->pluck('id')->all());
    }

    public function testSetRecursiveQueryConstraint()
    {
        Node::setRecursiveQueryConstraint(function (Builder $query) {
            $query->where('pivot_weight', '<', 7);
        });

        $nodes = Node::find(24)->descendants()->orderBy('id')->get();

        $this->assertEquals([54, 74, 84], $nodes->pluck('id')->all());

        Node::unsetRecursiveQueryConstraint();

        $nodes = Node::find(24)->descendants()->orderBy('id')->get();

        $this->assertEquals([54, 74, 84, 84], $nodes->pluck('id')->all());
    }

    public function testWithRecursiveQueryConstraint()
    {
        $nodes = Node::withRecursiveQueryConstraint(function (Builder $query) {
            $query->where('pivot_weight', '<', 7);
        }, function () {
            return Node::find(24)->descendants()->orderBy('id')->get();
        });

        $this->assertEquals([54, 74, 84], $nodes->pluck('id')->all());

        $nodes = Node::find(24)->descendants()->orderBy('id')->get();

        $this->assertEquals([54, 74, 84, 84], $nodes->pluck('id')->all());
    }

    public function testWithMaxDepth()
    {
        $nodes = Node::withMaxDepth(2, function () {
            return Node::find(24)->descendants;
        });

        $this->assertEquals([54, 74, 84], $nodes->pluck('id')->all());
    }

    public function testWithMaxDepthWithNegativeDepth()
    {
        $nodes = Node::withMaxDepth(-1, function () {
            return Node::find(54)->ancestors;
        });

        $this->assertEquals([14, 24, 104], $nodes->pluck('id')->all());
    }
}

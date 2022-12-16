<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Graph;

use Illuminate\Database\Eloquent\Builder;
use Staudenmeir\LaravelAdjacencyList\Tests\Graph\Models\Node;

class EloquentTest extends TestCase
{
    public function testScopeSubgraph()
    {
        if ($this->database === 'sqlsrv') {
            $this->markTestSkipped();
        }

        $constraint = function (Builder $query) {
            $query->whereIn('id', [3, 5]);
        };

        $graph = Node::subgraph($constraint)->orderBy('id')->get();

        $this->assertEquals([3, 5, 6, 7, 8, 8], $graph->pluck('id')->all());
    }

    public function testScopeSubgraphWithMaxDepth()
    {
        if ($this->database === 'sqlsrv') {
            $this->markTestSkipped();
        }

        $constraint = function (Builder $query) {
            $query->whereIn('id', [3, 5]);
        };

        $graph = Node::subgraph($constraint, 1)->orderBy('id')->get();

        $this->assertEquals([3, 5, 6, 7, 8], $graph->pluck('id')->all());
    }

    public function testChildren()
    {
        $children = Node::find(1)->children;

        $this->assertEquals([2, 3, 4, 5], $children->pluck('id')->all());
        $this->assertEquals(
            ['parent_id' => 1, 'child_id' => 2, 'label' => 'a', 'weight' => 1, 'created_at' => $this->getFormattedTestNow()],
            $children[0]->pivot->getAttributes()
        );
    }

    public function testChildrenAndSelf()
    {
        if ($this->database === 'sqlsrv') {
            $this->markTestSkipped();
        }

        $childrenAndSelf = Node::find(1)->childrenAndSelf;

        $this->assertEquals([1, 2, 3, 4, 5], $childrenAndSelf->pluck('id')->all());
    }

    public function testParents()
    {
        $parents = Node::find(5)->parents;

        $this->assertEquals([1, 2, 10], $parents->pluck('id')->all());
        $this->assertEquals(
            ['parent_id' => 1, 'child_id' => 5, 'label' => 'd', 'weight' => 4, 'created_at' => $this->getFormattedTestNow()],
            $parents[0]->pivot->getAttributes()
        );
    }

    public function testParentsAndSelf()
    {
        if ($this->database === 'sqlsrv') {
            $this->markTestSkipped();
        }

        $parentsAndSelf = Node::find(5)->parentsAndSelf()->orderByDesc('depth')->orderBy('id')->get();

        $this->assertEquals([5, 1, 2, 10], $parentsAndSelf->pluck('id')->all());
    }

    public function testScopeWhereDepth()
    {
        $nodes = Node::find(1)->descendants()->whereDepth(1)->get();

        $this->assertEquals([2, 3, 4, 5], $nodes->pluck('id')->all());
    }

    public function testScopeWhereDepthWithOperator()
    {
        $nodes = Node::find(1)->descendants()->whereDepth('>', 2)->orderBy('id')->get();

        $this->assertEquals([7, 8, 8, 8], $nodes->pluck('id')->all());
    }

    public function testScopeBreadthFirst()
    {
        $nodes = Node::find(1)->descendants()->breadthFirst()->orderByDesc('id')->get();

        $this->assertEquals([5, 4, 3, 2, 8, 7, 6, 5, 8, 8, 7, 8], $nodes->pluck('id')->all());
    }

    public function testScopeDepthFirst()
    {
        $nodes = Node::find(1)->descendants()->depthFirst()->get();

        $this->assertEquals([2, 5, 7, 8, 8, 3, 6, 4, 5, 7, 8, 8], $nodes->pluck('id')->all());
    }

    public function testSetRecursiveQueryConstraint()
    {
        Node::setRecursiveQueryConstraint(function (Builder $query) {
            $query->where('pivot_weight', '<', 7);
        });

        $nodes = Node::find(2)->descendants()->orderBy('id')->get();

        $this->assertEquals([5, 7, 8], $nodes->pluck('id')->all());

        Node::unsetRecursiveQueryConstraint();

        $nodes = Node::find(2)->descendants()->orderBy('id')->get();

        $this->assertEquals([5, 7, 8, 8], $nodes->pluck('id')->all());
    }

    public function testWithRecursiveQueryConstraint()
    {
        $nodes = Node::withRecursiveQueryConstraint(function (Builder $query) {
            $query->where('pivot_weight', '<', 7);
        }, function () {
            return Node::find(2)->descendants()->orderBy('id')->get();
        });

        $this->assertEquals([5, 7, 8], $nodes->pluck('id')->all());

        $nodes = Node::find(2)->descendants()->orderBy('id')->get();

        $this->assertEquals([5, 7, 8, 8], $nodes->pluck('id')->all());
    }

    public function testWithMaxDepth()
    {
        $nodes = Node::withMaxDepth(2, function () {
            return Node::find(2)->descendants;
        });

        $this->assertEquals([5, 7, 8], $nodes->pluck('id')->all());
    }

    public function testWithMaxDepthWithNegativeDepth()
    {
        $nodes = Node::withMaxDepth(-1, function () {
            return Node::find(5)->ancestors;
        });

        $this->assertEquals([1, 2, 10], $nodes->pluck('id')->all());
    }
}

<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Graph;

use Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Graph\Ancestors;
use Staudenmeir\LaravelAdjacencyList\Tests\Graph\Models\Node;
use Staudenmeir\LaravelAdjacencyList\Tests\Graph\Models\NodeWithCycleDetection;
use Staudenmeir\LaravelAdjacencyList\Tests\Graph\Models\NodeWithCycleDetectionAndStart;

class AncestorsTest extends TestCase
{
    public function testLazyLoading()
    {
        $ancestors = Node::find(5)->ancestors;

        $this->assertEquals([1, 2, 10, 1, 9], $ancestors->pluck('id')->all());
        $this->assertEquals([-1, -1, -1, -2, -2], $ancestors->pluck('depth')->all());
        $this->assertEquals(['1', '2', '10', '2.1', '2.9'], $ancestors->pluck('path')->all());
        $this->assertEquals(
            ['node-1', 'node-2', 'node-10', 'node-2/node-1', 'node-2/node-9'],
            $ancestors->pluck('slug_path')->all()
        );
        $this->assertEquals(
            ['node-1', 'node-2', 'node-10', 'node-1/node-2', 'node-9/node-2'],
            $ancestors->pluck('reverse_slug_path')->all()
        );
        $this->assertEquals(
            ['parent_id' => 1, 'child_id' => 5, 'label' => 'd', 'weight' => 4, 'created_at' => $this->getFormattedTestNow()],
            $ancestors[0]->pivot->getAttributes()
        );
    }

    public function testLazyLoadingWithCycleDetection()
    {
        $this->seedCycle();

        $ancestors = NodeWithCycleDetection::find(12)->ancestors;

        $this->assertEquals([14, 13, 12], $ancestors->pluck('id')->all());
        $this->assertEquals([-1, -2, -3], $ancestors->pluck('depth')->all());
    }

    public function testLazyLoadingWithCycleDetectionAndStart()
    {
        $this->seedCycle();

        $ancestors = NodeWithCycleDetectionAndStart::find(12)->ancestors;

        $this->assertEquals([14, 13, 12, 14], $ancestors->pluck('id')->all());
        $this->assertEquals([-1, -2, -3, -4], $ancestors->pluck('depth')->all());
        $this->assertEquals([0, 0, 0, 1], $ancestors->pluck('is_cycle')->all());
    }

    public function testLazyLoadingAndSelf()
    {
        if ($this->database === 'sqlsrv') {
            $this->markTestSkipped();
        }

        $ancestorsAndSelf = Node::find(5)->ancestorsAndSelf;

        $this->assertEquals([5, 1, 2, 10, 1, 9], $ancestorsAndSelf->pluck('id')->all());
        $this->assertEquals([0, -1, -1, -1, -2, -2], $ancestorsAndSelf->pluck('depth')->all());
        $this->assertEquals(
            ['5', '5.1', '5.2', '5.10', '5.2.1', '5.2.9'],
            $ancestorsAndSelf->pluck('path')->all()
        );
        $this->assertEquals(
            ['node-5', 'node-5/node-1', 'node-5/node-2', 'node-5/node-10', 'node-5/node-2/node-1', 'node-5/node-2/node-9'],
            $ancestorsAndSelf->pluck('slug_path')->all()
        );
        $this->assertEquals(
            ['parent_id' => null, 'child_id' => null, 'label' => null, 'weight' => null, 'created_at' => null],
            $ancestorsAndSelf[0]->pivot->getAttributes()
        );
        $this->assertEquals(
            ['parent_id' => 1, 'child_id' => 5, 'label' => 'd', 'weight' => 4, 'created_at' => $this->getFormattedTestNow()],
            $ancestorsAndSelf[1]->pivot->getAttributes()
        );
    }

    public function testLazyLoadingAndSelfWithCycleDetection()
    {
        if ($this->database === 'sqlsrv') {
            $this->markTestSkipped();
        }

        $this->seedCycle();

        $ancestorsAndSelf = NodeWithCycleDetection::find(12)->ancestorsAndSelf;

        $this->assertEquals([12, 14, 13], $ancestorsAndSelf->pluck('id')->all());
        $this->assertEquals([0, -1, -2], $ancestorsAndSelf->pluck('depth')->all());
    }

    public function testLazyLoadingAndSelfWithCycleDetectionAndStart()
    {
        if ($this->database === 'sqlsrv') {
            $this->markTestSkipped();
        }

        $this->seedCycle();

        $ancestorsAndSelf = NodeWithCycleDetectionAndStart::find(12)->ancestorsAndSelf;

        $this->assertEquals([12, 14, 13, 12], $ancestorsAndSelf->pluck('id')->all());
        $this->assertEquals([0, -1, -2, -3], $ancestorsAndSelf->pluck('depth')->all());
        $this->assertEquals([0, 0, 0, 1], $ancestorsAndSelf->pluck('is_cycle')->all());
    }

    public function testEagerLoading()
    {
        $nodes = Node::with([
            'ancestors' => fn (Ancestors $query) => $query->orderByDesc('depth')->orderBy('id'),
        ])->get();

        $this->assertEquals([], $nodes[0]->ancestors->pluck('id')->all());
        $this->assertEquals([1, 9], $nodes[1]->ancestors->pluck('id')->all());
        $this->assertEquals([1, 2, 10, 1, 9], $nodes[4]->ancestors->pluck('id')->all());
        $this->assertEquals([-1, -1, -1, -2, -2], $nodes[4]->ancestors->pluck('depth')->all());
        $this->assertEquals(['1', '2', '10', '2.1', '2.9'], $nodes[4]->ancestors->pluck('path')->all());
        $this->assertEquals(
            ['parent_id' => 1, 'child_id' => 5, 'label' => 'd', 'weight' => 4, 'created_at' => $this->getFormattedTestNow()],
            $nodes[4]->ancestors[0]->pivot->getAttributes()
        );
    }

    public function testEagerLoadingWithCycleDetection()
    {
        $this->seedCycle();

        $nodes = NodeWithCycleDetection::with([
            'ancestors' => fn (Ancestors $query) => $query->orderByDesc('depth'),
        ])->findMany([12, 13, 14]);

        $this->assertEquals([14, 13, 12], $nodes[0]->ancestors->pluck('id')->all());
        $this->assertEquals([-1, -2, -3], $nodes[0]->ancestors->pluck('depth')->all());
    }

    public function testEagerLoadingWithCycleDetectionAndStart()
    {
        $this->seedCycle();

        $nodes = NodeWithCycleDetectionAndStart::with([
            'ancestors' => fn (Ancestors $query) => $query->orderByDesc('depth'),
        ])->findMany([12, 13, 14]);

        $this->assertEquals([14, 13, 12, 14], $nodes[0]->ancestors->pluck('id')->all());
        $this->assertEquals([-1, -2, -3, -4], $nodes[0]->ancestors->pluck('depth')->all());
        $this->assertEquals([0, 0, 0, 1], $nodes[0]->ancestors->pluck('is_cycle')->all());
    }

    public function testEagerLoadingAndSelf()
    {
        if ($this->database === 'sqlsrv') {
            $this->markTestSkipped();
        }

        $nodes = Node::with('ancestorsAndSelf')->get();

        $this->assertEquals([1], $nodes[0]->ancestorsAndSelf->pluck('id')->all());
        $this->assertEquals([2, 1, 9], $nodes[1]->ancestorsAndSelf->pluck('id')->all());
        $this->assertEquals([5, 1, 2, 10, 1, 9], $nodes[4]->ancestorsAndSelf->pluck('id')->all());
        $this->assertEquals(
            ['5', '5.1', '5.2', '5.10', '5.2.1', '5.2.9'],
            $nodes[4]->ancestorsAndSelf->pluck('path')->all()
        );
        $this->assertEquals(
            ['parent_id' => null, 'child_id' => null, 'label' => null, 'weight' => null, 'created_at' => null],
            $nodes[4]->ancestorsAndSelf[0]->pivot->getAttributes()
        );
        $this->assertEquals(
            ['parent_id' => 1, 'child_id' => 5, 'label' => 'd', 'weight' => 4, 'created_at' => $this->getFormattedTestNow()],
            $nodes[4]->ancestorsAndSelf[1]->pivot->getAttributes()
        );
    }

    public function testEagerLoadingAndSelfWithCycleDetection()
    {
        if ($this->database === 'sqlsrv') {
            $this->markTestSkipped();
        }

        $this->seedCycle();

        $nodes = NodeWithCycleDetection::with('ancestorsAndSelf')->findMany([12, 13, 14]);

        $this->assertEquals([12, 14, 13], $nodes[0]->ancestorsAndSelf->pluck('id')->all());
        $this->assertEquals([0, -1, -2], $nodes[0]->ancestorsAndSelf->pluck('depth')->all());
    }

    public function testEagerLoadingAndSelfWithCycleDetectionAndStart()
    {
        if ($this->database === 'sqlsrv') {
            $this->markTestSkipped();
        }

        $this->seedCycle();

        $nodes = NodeWithCycleDetectionAndStart::with('ancestorsAndSelf')->findMany([12, 13, 14]);

        $this->assertEquals([12, 14, 13, 12], $nodes[0]->ancestorsAndSelf->pluck('id')->all());
        $this->assertEquals([0, -1, -2, -3], $nodes[0]->ancestorsAndSelf->pluck('depth')->all());
        $this->assertEquals([0, 0, 0, 1], $nodes[0]->ancestorsAndSelf->pluck('is_cycle')->all());
    }

    public function testLazyEagerLoading()
    {
        $nodes = Node::all()->load([
            'ancestors' => fn (Ancestors $query) => $query->orderByDesc('depth')->orderBy('id'),
        ]);

        $this->assertEquals([], $nodes[0]->ancestors->pluck('id')->all());
        $this->assertEquals([1, 9], $nodes[1]->ancestors->pluck('id')->all());
        $this->assertEquals([1, 2, 10, 1, 9], $nodes[4]->ancestors->pluck('id')->all());
        $this->assertEquals([-1, -1, -1, -2, -2], $nodes[4]->ancestors->pluck('depth')->all());
        $this->assertEquals(['1', '2', '10', '2.1', '2.9'], $nodes[4]->ancestors->pluck('path')->all());
        $this->assertEquals(
            ['parent_id' => 1, 'child_id' => 5, 'label' => 'd', 'weight' => 4, 'created_at' => $this->getFormattedTestNow()],
            $nodes[4]->ancestors[0]->pivot->getAttributes()
        );
    }

    public function testLazyEagerLoadingAndSelf()
    {
        if ($this->database === 'sqlsrv') {
            $this->markTestSkipped();
        }

        $nodes = Node::all()->load('ancestorsAndSelf');

        $this->assertEquals([1], $nodes[0]->ancestorsAndSelf->pluck('id')->all());
        $this->assertEquals([2, 1, 9], $nodes[1]->ancestorsAndSelf->pluck('id')->all());
        $this->assertEquals([5, 1, 2, 10, 1, 9], $nodes[4]->ancestorsAndSelf->pluck('id')->all());
        $this->assertEquals(
            ['5', '5.1', '5.2', '5.10', '5.2.1', '5.2.9'],
            $nodes[4]->ancestorsAndSelf->pluck('path')->all()
        );
        $this->assertEquals(
            ['parent_id' => null, 'child_id' => null, 'label' => null, 'weight' => null, 'created_at' => null],
            $nodes[4]->ancestorsAndSelf[0]->pivot->getAttributes()
        );
        $this->assertEquals(
            ['parent_id' => 1, 'child_id' => 5, 'label' => 'd', 'weight' => 4, 'created_at' => $this->getFormattedTestNow()],
            $nodes[4]->ancestorsAndSelf[1]->pivot->getAttributes()
        );
    }

    public function testExistenceQuery()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $descendants = Node::first()->descendants()->has('ancestors', '>', 2)->get();

        $this->assertEquals([5, 5, 7, 8, 7, 8, 8, 8], $descendants->pluck('id')->all());
    }

    public function testExistenceQueryAndSelf()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $descendants = Node::first()->descendants()->has('ancestorsAndSelf', '>', 2)->get();

        $this->assertEquals([2, 5, 5, 6, 7, 8, 7, 8, 8, 8], $descendants->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelation()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $nodes = Node::has('ancestors')->get();

        $this->assertEquals([2, 3, 4, 5, 6, 7, 8], $nodes->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelationAndSelf()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $nodes = Node::has('ancestorsAndSelf', '>', 2)->get();

        $this->assertEquals([2, 5, 6, 7, 8], $nodes->pluck('id')->all());
    }

    public function testWithSumForSelfRelation()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $user = Node::withSum('ancestors', 'pivot_weight')->find(5);

        $this->assertEquals(1 + 4 + 5 + 10 + 11, $user->ancestors_sum_pivot_weight);
    }

    public function testWithSumForSelfRelationAndSelf()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $user = Node::withSum('ancestorsAndSelf', 'pivot_weight')->find(5);

        $this->assertEquals(1 + 4 + 5 + 10 + 11, $user->ancestors_and_self_sum_pivot_weight);
    }

    public function testDelete()
    {
        if ($this->database === 'mariadb') {
            $this->markTestSkipped();
        }

        $affected = Node::find(5)->ancestors()->delete();

        $this->assertEquals(4, $affected);
        $this->assertNotNull(Node::withTrashed()->find(2)->deleted_at);
        $this->assertNull(Node::find(7)->deleted_at);
    }
}

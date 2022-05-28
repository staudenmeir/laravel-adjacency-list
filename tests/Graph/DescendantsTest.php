<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Graph;

use Carbon\Carbon;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Graph\Descendants;
use Staudenmeir\LaravelAdjacencyList\Tests\Graph\Models\Node;
use Staudenmeir\LaravelAdjacencyList\Tests\Graph\Models\NodeWithCycleDetection;
use Staudenmeir\LaravelAdjacencyList\Tests\Graph\Models\NodeWithCycleDetectionAndStart;

class DescendantsTest extends TestCase
{
    public function testLazyLoading()
    {
        $descendants = Node::find(24)->descendants;

        $this->assertEquals([54, 74, 84, 84], $descendants->pluck('id')->all());
        $this->assertEquals([1, 2, 2, 3], $descendants->pluck('depth')->all());
        $this->assertEquals(['54', '54.74', '54.84', '54.74.84'], $descendants->pluck('path')->all());
        $this->assertEquals(
            ['node-54', 'node-54/node-74', 'node-54/node-84', 'node-54/node-74/node-84'],
            $descendants->pluck('slug_path')->all()
        );
        $this->assertEquals(
            ['parent_id' => 24, 'child_id' => 54, 'label' => 'e', 'weight' => 5, 'created_at' => Carbon::getTestNow()],
            $descendants[0]->pivot->getAttributes()
        );
    }

    public function testLazyLoadingWithCycleDetection()
    {
        $this->seedCycle();

        $descendants = NodeWithCycleDetection::find(124)->descendants;

        $this->assertEquals([134, 144, 124], $descendants->pluck('id')->all());
        $this->assertEquals([1, 2, 3], $descendants->pluck('depth')->all());
    }

    public function testLazyLoadingWithCycleDetectionAndStart()
    {
        $this->seedCycle();

        $descendants = NodeWithCycleDetectionAndStart::find(124)->descendants;

        $this->assertEquals([134, 144, 124, 134], $descendants->pluck('id')->all());
        $this->assertEquals([1, 2, 3, 4], $descendants->pluck('depth')->all());
        $this->assertEquals([0, 0, 0, 1], $descendants->pluck('is_cycle')->all());
    }

    public function testLazyLoadingAndSelf()
    {
        $descendantsAndSelf = Node::find(24)->descendantsAndSelf;

        $this->assertEquals([24, 54, 74, 84, 84], $descendantsAndSelf->pluck('id')->all());
        $this->assertEquals([0, 1, 2, 2, 3], $descendantsAndSelf->pluck('depth')->all());
        $this->assertEquals(
            ['24', '24.54', '24.54.74', '24.54.84', '24.54.74.84'],
            $descendantsAndSelf->pluck('path')->all()
        );
        $this->assertEquals(
            ['node-24', 'node-24/node-54', 'node-24/node-54/node-74', 'node-24/node-54/node-84', 'node-24/node-54/node-74/node-84'],
            $descendantsAndSelf->pluck('slug_path')->all()
        );
        $this->assertEquals(
            ['parent_id' => null, 'child_id' => null, 'label' => null, 'weight' => null, 'created_at' => null],
            $descendantsAndSelf[0]->pivot->getAttributes()
        );
        $this->assertEquals(
            ['parent_id' => 24, 'child_id' => 54, 'label' => 'e', 'weight' => 5, 'created_at' => Carbon::getTestNow()],
            $descendantsAndSelf[1]->pivot->getAttributes()
        );
    }

    public function testLazyLoadingAndSelfWithCycleDetection()
    {
        $this->seedCycle();

        $descendantsAndSelf = NodeWithCycleDetection::find(124)->descendantsAndSelf;

        $this->assertEquals([124, 134, 144], $descendantsAndSelf->pluck('id')->all());
        $this->assertEquals([0, 1, 2], $descendantsAndSelf->pluck('depth')->all());
    }

    public function testLazyLoadingAndSelfWithCycleDetectionAndStart()
    {
        $this->seedCycle();

        $descendantsAndSelf = NodeWithCycleDetectionAndStart::find(124)->descendantsAndSelf;

        $this->assertEquals([124, 134, 144, 124], $descendantsAndSelf->pluck('id')->all());
        $this->assertEquals([0, 1, 2, 3], $descendantsAndSelf->pluck('depth')->all());
        $this->assertEquals([0, 0, 0, 1], $descendantsAndSelf->pluck('is_cycle')->all());
    }

    public function testEagerLoading()
    {
        $nodes = Node::with(['descendants' => function (Descendants $query) {
            $query->orderBy('id')->orderBy('depth');
        }])->get();

        $this->assertEquals(
            [24, 34, 44, 54, 54, 64, 74, 74, 84, 84, 84, 84],
            $nodes[0]->descendants->pluck('id')->all()
        );
        $this->assertEquals(
            [54, 74, 84, 84],
            $nodes[1]->descendants->pluck('id')->all()
        );
        $this->assertEquals([1, 2, 2, 3], $nodes[1]->descendants->pluck('depth')->all());
        $this->assertEquals(
            ['54', '54.74', '54.84', '54.74.84'],
            $nodes[1]->descendants->pluck('path')->all()
        );
        $this->assertEquals(
            ['parent_id' => 24, 'child_id' => 54, 'label' => 'e', 'weight' => 5, 'created_at' => Carbon::getTestNow()],
            $nodes[1]->descendants[0]->pivot->getAttributes()
        );
    }

    public function testEagerLoadingWithCycleDetection()
    {
        $this->seedCycle();

        $nodes = NodeWithCycleDetection::with('descendants')->findMany([124, 134, 144]);

        $this->assertEquals([134, 144, 124], $nodes[0]->descendants->pluck('id')->all());
        $this->assertEquals([1, 2, 3], $nodes[0]->descendants->pluck('depth')->all());
    }

    public function testEagerLoadingWithCycleDetectionAndStart()
    {
        $this->seedCycle();

        $nodes = NodeWithCycleDetectionAndStart::with('descendants')->findMany([124, 134, 144]);

        $this->assertEquals([134, 144, 124, 134], $nodes[0]->descendants->pluck('id')->all());
        $this->assertEquals([1, 2, 3, 4], $nodes[0]->descendants->pluck('depth')->all());
        $this->assertEquals([0, 0, 0, 1], $nodes[0]->descendants->pluck('is_cycle')->all());
    }

    public function testEagerLoadingAndSelf()
    {
        $nodes = Node::with(['descendantsAndSelf' => function (Descendants $query) {
            $query->orderBy('id')->orderBy('depth');
        }])->get();

        $this->assertEquals(
            [14, 24, 34, 44, 54, 54, 64, 74, 74, 84, 84, 84, 84],
            $nodes[0]->descendantsAndSelf->pluck('id')->all()
        );
        $this->assertEquals(
            [24, 54, 74, 84, 84],
            $nodes[1]->descendantsAndSelf->pluck('id')->all()
        );
        $this->assertEquals([0, 1, 2, 2, 3], $nodes[1]->descendantsAndSelf->pluck('depth')->all());
        $this->assertEquals(
            ['24', '24.54', '24.54.74', '24.54.84', '24.54.74.84'],
            $nodes[1]->descendantsAndSelf->pluck('path')->all()
        );
        $this->assertEquals(
            ['parent_id' => null, 'child_id' => null, 'label' => null, 'weight' => null, 'created_at' => null],
            $nodes[1]->descendantsAndSelf[0]->pivot->getAttributes()
        );
        $this->assertEquals(
            ['parent_id' => 24, 'child_id' => 54, 'label' => 'e', 'weight' => 5, 'created_at' => Carbon::getTestNow()],
            $nodes[1]->descendantsAndSelf[1]->pivot->getAttributes()
        );
    }

    public function testEagerLoadingAndSelfWithCycleDetection()
    {
        $this->seedCycle();

        $nodes = NodeWithCycleDetection::with('descendantsAndSelf')->findMany([124, 134, 144]);

        $this->assertEquals([124, 134, 144], $nodes[0]->descendantsAndSelf->pluck('id')->all());
        $this->assertEquals([0, 1, 2], $nodes[0]->descendantsAndSelf->pluck('depth')->all());
    }

    public function testEagerLoadingAndSelfWithCycleDetectionAndStart()
    {
        $this->seedCycle();

        $nodes = NodeWithCycleDetectionAndStart::with('descendantsAndSelf')->findMany([124, 134, 144]);

        $this->assertEquals([124, 134, 144, 124], $nodes[0]->descendantsAndSelf->pluck('id')->all());
        $this->assertEquals([0, 1, 2, 3], $nodes[0]->descendantsAndSelf->pluck('depth')->all());
        $this->assertEquals([0, 0, 0, 1], $nodes[0]->descendantsAndSelf->pluck('is_cycle')->all());
    }

    public function testLazyEagerLoading()
    {
        $nodes = Node::all()->load(['descendants' => function (Descendants $query) {
            $query->orderBy('id')->orderBy('depth');
        }]);

        $this->assertEquals(
            [24, 34, 44, 54, 54, 64, 74, 74, 84, 84, 84, 84],
            $nodes[0]->descendants->pluck('id')->all()
        );
        $this->assertEquals(
            [54, 74, 84, 84],
            $nodes[1]->descendants->pluck('id')->all()
        );
        $this->assertEquals([1, 2, 2, 3], $nodes[1]->descendants->pluck('depth')->all());
        $this->assertEquals(
            ['54', '54.74', '54.84', '54.74.84'],
            $nodes[1]->descendants->pluck('path')->all()
        );
        $this->assertEquals(
            ['parent_id' => 24, 'child_id' => 54, 'label' => 'e', 'weight' => 5, 'created_at' => Carbon::getTestNow()],
            $nodes[1]->descendants[0]->pivot->getAttributes()
        );
    }

    public function testLazyEagerLoadingAndSelf()
    {
        $nodes = Node::all()->load(['descendantsAndSelf' => function (Descendants $query) {
            $query->orderBy('id')->orderBy('depth');
        }]);

        $this->assertEquals(
            [14, 24, 34, 44, 54, 54, 64, 74, 74, 84, 84, 84, 84],
            $nodes[0]->descendantsAndSelf->pluck('id')->all()
        );
        $this->assertEquals(
            [24, 54, 74, 84, 84],
            $nodes[1]->descendantsAndSelf->pluck('id')->all()
        );
        $this->assertEquals([0, 1, 2, 2, 3], $nodes[1]->descendantsAndSelf->pluck('depth')->all());
        $this->assertEquals(
            ['24', '24.54', '24.54.74', '24.54.84', '24.54.74.84'],
            $nodes[1]->descendantsAndSelf->pluck('path')->all()
        );
        $this->assertEquals(
            ['parent_id' => null, 'child_id' => null, 'label' => null, 'weight' => null, 'created_at' => null],
            $nodes[1]->descendantsAndSelf[0]->pivot->getAttributes()
        );
        $this->assertEquals(
            ['parent_id' => 24, 'child_id' => 54, 'label' => 'e', 'weight' => 5, 'created_at' => Carbon::getTestNow()],
            $nodes[1]->descendantsAndSelf[1]->pivot->getAttributes()
        );
    }

    public function testExistenceQuery()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $ancestors = Node::find(54)->ancestors()->has('descendants', '>', 2)->get();

        $this->assertEquals([14, 24, 104, 14, 94], $ancestors->pluck('id')->all());
    }

    public function testExistenceQueryWithCycleDetection()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $this->seedCycle();

        $ancestors = NodeWithCycleDetection::find(124)->ancestors()->has('descendants', '>', 2)->get();

        $this->assertEquals([144, 134, 124], $ancestors->pluck('id')->all());
    }

    public function testExistenceQueryWithCycleDetectionAndStart()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $this->seedCycle();

        $ancestors = NodeWithCycleDetectionAndStart::find(124)->ancestors()->has('descendants', '>', 3)->get();

        $this->assertEquals([144, 134, 124, 144], $ancestors->pluck('id')->all());
    }

    public function testExistenceQueryAndSelf()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $ancestors = Node::find(54)->ancestors()->has('descendantsAndSelf', '>', 2)->get();

        $this->assertEquals([14, 24, 104, 14, 94], $ancestors->pluck('id')->all());
    }

    public function testExistenceQueryAndSelfWithCycleDetection()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $this->seedCycle();

        $ancestors = NodeWithCycleDetection::find(124)->ancestors()->has('descendantsAndSelf', '>', 2)->get();

        $this->assertEquals([144, 134, 124], $ancestors->pluck('id')->all());
    }

    public function testExistenceQueryAndSelfWithCycleDetectionAndStart()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $this->seedCycle();

        $ancestors = NodeWithCycleDetectionAndStart::find(124)->ancestors()->has('descendantsAndSelf', '>', 2)->get();

        $this->assertEquals([144, 134, 124, 144], $ancestors->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelation()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $nodes = Node::has('descendants')->get();

        $this->assertEquals([14, 24, 34, 54, 74, 94, 104], $nodes->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelationAndSelf()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $nodes = Node::has('descendantsAndSelf', '>', 2)->get();

        $this->assertEquals([14, 24, 54, 94, 104], $nodes->pluck('id')->all());
    }

    public function testWithSumForSelfRelation()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $user = Node::withSum('descendants', 'pivot_weight')->find(24);

        $this->assertEquals(5 + 7 + 8 + 9, $user->descendants_sum_pivot_weight);
    }

    public function testWithSumForSelfRelationAndSelf()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $user = Node::withSum('descendantsAndSelf', 'pivot_weight')->find(24);

        $this->assertEquals(5 + 7 + 8 + 9, $user->descendants_and_self_sum_pivot_weight);
    }

    public function testDelete()
    {
        if ($this->database === 'mariadb') {
            $this->markTestSkipped();
        }

        $affected = Node::find(24)->descendants()->delete();

        $this->assertEquals(3, $affected);
        $this->assertNotNull(Node::withTrashed()->find(54)->deleted_at);
        $this->assertNull(Node::find(14)->deleted_at);
    }
}

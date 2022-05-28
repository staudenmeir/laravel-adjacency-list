<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Graph;

use Carbon\Carbon;
use Staudenmeir\LaravelAdjacencyList\Tests\Graph\Models\Node;
use Staudenmeir\LaravelAdjacencyList\Tests\Graph\Models\NodeWithCycleDetection;
use Staudenmeir\LaravelAdjacencyList\Tests\Graph\Models\NodeWithCycleDetectionAndStart;

class AncestorsTest extends TestCase
{
    public function testLazyLoading()
    {
        $ancestors = Node::find(54)->ancestors;

        $this->assertEquals([14, 24, 104, 14, 94], $ancestors->pluck('id')->all());
        $this->assertEquals([-1, -1, -1, -2, -2], $ancestors->pluck('depth')->all());
        $this->assertEquals(['14', '24', '104', '24.14', '24.94'], $ancestors->pluck('path')->all());
        $this->assertEquals(
            ['node-14', 'node-24', 'node-104', 'node-24/node-14', 'node-24/node-94'],
            $ancestors->pluck('slug_path')->all()
        );
        $this->assertEquals(
            ['parent_id' => 14, 'child_id' => 54, 'label' => 'd', 'weight' => 4, 'created_at' => Carbon::getTestNow()],
            $ancestors[0]->pivot->getAttributes()
        );
    }

    public function testLazyLoadingWithCycleDetection()
    {
        $this->seedCycle();

        $ancestors = NodeWithCycleDetection::find(124)->ancestors;

        $this->assertEquals([144, 134, 124], $ancestors->pluck('id')->all());
        $this->assertEquals([-1, -2, -3], $ancestors->pluck('depth')->all());
    }

    public function testLazyLoadingWithCycleDetectionAndStart()
    {
        $this->seedCycle();

        $ancestors = NodeWithCycleDetectionAndStart::find(124)->ancestors;

        $this->assertEquals([144, 134, 124, 144], $ancestors->pluck('id')->all());
        $this->assertEquals([-1, -2, -3, -4], $ancestors->pluck('depth')->all());
        $this->assertEquals([0, 0, 0, 1], $ancestors->pluck('is_cycle')->all());
    }

    public function testLazyLoadingAndSelf()
    {
        $ancestorsAndSelf = Node::find(54)->ancestorsAndSelf;

        $this->assertEquals([54, 14, 24, 104, 14, 94], $ancestorsAndSelf->pluck('id')->all());
        $this->assertEquals([0, -1, -1, -1, -2, -2], $ancestorsAndSelf->pluck('depth')->all());
        $this->assertEquals(
            ['54', '54.14', '54.24', '54.104', '54.24.14', '54.24.94'],
            $ancestorsAndSelf->pluck('path')->all()
        );
        $this->assertEquals(
            ['node-54', 'node-54/node-14', 'node-54/node-24', 'node-54/node-104', 'node-54/node-24/node-14', 'node-54/node-24/node-94'],
            $ancestorsAndSelf->pluck('slug_path')->all()
        );
        $this->assertEquals(
            ['parent_id' => null, 'child_id' => null, 'label' => null, 'weight' => null, 'created_at' => null],
            $ancestorsAndSelf[0]->pivot->getAttributes()
        );
        $this->assertEquals(
            ['parent_id' => 14, 'child_id' => 54, 'label' => 'd', 'weight' => 4, 'created_at' => Carbon::getTestNow()],
            $ancestorsAndSelf[1]->pivot->getAttributes()
        );
    }

    public function testLazyLoadingAndSelfWithCycleDetection()
    {
        $this->seedCycle();

        $ancestorsAndSelf = NodeWithCycleDetection::find(124)->ancestorsAndSelf;

        $this->assertEquals([124, 144, 134], $ancestorsAndSelf->pluck('id')->all());
        $this->assertEquals([0, -1, -2], $ancestorsAndSelf->pluck('depth')->all());
    }

    public function testLazyLoadingAndSelfWithCycleDetectionAndStart()
    {
        $this->seedCycle();

        $ancestorsAndSelf = NodeWithCycleDetectionAndStart::find(124)->ancestorsAndSelf;

        $this->assertEquals([124, 144, 134, 124], $ancestorsAndSelf->pluck('id')->all());
        $this->assertEquals([0, -1, -2, -3], $ancestorsAndSelf->pluck('depth')->all());
        $this->assertEquals([0, 0, 0, 1], $ancestorsAndSelf->pluck('is_cycle')->all());
    }

    public function testEagerLoading()
    {
        $nodes = Node::with('ancestors')->get();

        $this->assertEquals([], $nodes[0]->ancestors->pluck('id')->all());
        $this->assertEquals([14, 94], $nodes[1]->ancestors->pluck('id')->all());
        $this->assertEquals([14, 24, 104, 14, 94], $nodes[4]->ancestors->pluck('id')->all());
        $this->assertEquals([-1, -1, -1, -2, -2], $nodes[4]->ancestors->pluck('depth')->all());
        $this->assertEquals(['14', '24', '104', '24.14', '24.94'], $nodes[4]->ancestors->pluck('path')->all());
        $this->assertEquals(
            ['parent_id' => 14, 'child_id' => 54, 'label' => 'd', 'weight' => 4, 'created_at' => Carbon::getTestNow()],
            $nodes[4]->ancestors[0]->pivot->getAttributes()
        );
    }

    public function testEagerLoadingWithCycleDetection()
    {
        $this->seedCycle();

        $nodes = NodeWithCycleDetection::with('ancestors')->findMany([124, 134, 144]);

        $this->assertEquals([144, 134, 124], $nodes[0]->ancestors->pluck('id')->all());
        $this->assertEquals([-1, -2, -3], $nodes[0]->ancestors->pluck('depth')->all());
    }

    public function testEagerLoadingWithCycleDetectionAndStart()
    {
        $this->seedCycle();

        $nodes = NodeWithCycleDetectionAndStart::with('ancestors')->findMany([124, 134, 144]);

        $this->assertEquals([144, 134, 124, 144], $nodes[0]->ancestors->pluck('id')->all());
        $this->assertEquals([-1, -2, -3, -4], $nodes[0]->ancestors->pluck('depth')->all());
        $this->assertEquals([0, 0, 0, 1], $nodes[0]->ancestors->pluck('is_cycle')->all());
    }

    public function testEagerLoadingAndSelf()
    {
        $nodes = Node::with('ancestorsAndSelf')->get();

        $this->assertEquals([14], $nodes[0]->ancestorsAndSelf->pluck('id')->all());
        $this->assertEquals([24, 14, 94], $nodes[1]->ancestorsAndSelf->pluck('id')->all());
        $this->assertEquals([54, 14, 24, 104, 14, 94], $nodes[4]->ancestorsAndSelf->pluck('id')->all());
        $this->assertEquals(
            ['54', '54.14', '54.24', '54.104', '54.24.14', '54.24.94'],
            $nodes[4]->ancestorsAndSelf->pluck('path')->all()
        );
        $this->assertEquals(
            ['parent_id' => null, 'child_id' => null, 'label' => null, 'weight' => null, 'created_at' => null],
            $nodes[4]->ancestorsAndSelf[0]->pivot->getAttributes()
        );
        $this->assertEquals(
            ['parent_id' => 14, 'child_id' => 54, 'label' => 'd', 'weight' => 4, 'created_at' => Carbon::getTestNow()],
            $nodes[4]->ancestorsAndSelf[1]->pivot->getAttributes()
        );
    }

    public function testEagerLoadingAndSelfWithCycleDetection()
    {
        $this->seedCycle();

        $nodes = NodeWithCycleDetection::with('ancestorsAndSelf')->findMany([124, 134, 144]);

        $this->assertEquals([124, 144, 134], $nodes[0]->ancestorsAndSelf->pluck('id')->all());
        $this->assertEquals([0, -1, -2], $nodes[0]->ancestorsAndSelf->pluck('depth')->all());
    }

    public function testEagerLoadingAndSelfWithCycleDetectionAndStart()
    {
        $this->seedCycle();

        $nodes = NodeWithCycleDetectionAndStart::with('ancestorsAndSelf')->findMany([124, 134, 144]);

        $this->assertEquals([124, 144, 134, 124], $nodes[0]->ancestorsAndSelf->pluck('id')->all());
        $this->assertEquals([0, -1, -2, -3], $nodes[0]->ancestorsAndSelf->pluck('depth')->all());
        $this->assertEquals([0, 0, 0, 1], $nodes[0]->ancestorsAndSelf->pluck('is_cycle')->all());
    }

    public function testLazyEagerLoading()
    {
        $nodes = Node::all()->load('ancestors');

        $this->assertEquals([], $nodes[0]->ancestors->pluck('id')->all());
        $this->assertEquals([14, 94], $nodes[1]->ancestors->pluck('id')->all());
        $this->assertEquals([14, 24, 104, 14, 94], $nodes[4]->ancestors->pluck('id')->all());
        $this->assertEquals([-1, -1, -1, -2, -2], $nodes[4]->ancestors->pluck('depth')->all());
        $this->assertEquals(['14', '24', '104', '24.14', '24.94'], $nodes[4]->ancestors->pluck('path')->all());
        $this->assertEquals(
            ['parent_id' => 14, 'child_id' => 54, 'label' => 'd', 'weight' => 4, 'created_at' => Carbon::getTestNow()],
            $nodes[4]->ancestors[0]->pivot->getAttributes()
        );
    }

    public function testLazyEagerLoadingAndSelf()
    {
        $nodes = Node::all()->load('ancestorsAndSelf');

        $this->assertEquals([14], $nodes[0]->ancestorsAndSelf->pluck('id')->all());
        $this->assertEquals([24, 14, 94], $nodes[1]->ancestorsAndSelf->pluck('id')->all());
        $this->assertEquals([54, 14, 24, 104, 14, 94], $nodes[4]->ancestorsAndSelf->pluck('id')->all());
        $this->assertEquals(
            ['54', '54.14', '54.24', '54.104', '54.24.14', '54.24.94'],
            $nodes[4]->ancestorsAndSelf->pluck('path')->all()
        );
        $this->assertEquals(
            ['parent_id' => null, 'child_id' => null, 'label' => null, 'weight' => null, 'created_at' => null],
            $nodes[4]->ancestorsAndSelf[0]->pivot->getAttributes()
        );
        $this->assertEquals(
            ['parent_id' => 14, 'child_id' => 54, 'label' => 'd', 'weight' => 4, 'created_at' => Carbon::getTestNow()],
            $nodes[4]->ancestorsAndSelf[1]->pivot->getAttributes()
        );
    }

    public function testExistenceQuery()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $descendants = Node::first()->descendants()->has('ancestors', '>', 2)->get();

        $this->assertEquals([54, 54, 74, 84, 74, 84, 84, 84], $descendants->pluck('id')->all());
    }

    //

    public function testExistenceQueryAndSelf()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $descendants = Node::first()->descendants()->has('ancestorsAndSelf', '>', 2)->get();

        $this->assertEquals([24, 54, 54, 64, 74, 84, 74, 84, 84, 84], $descendants->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelation()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $nodes = Node::has('ancestors')->get();

        $this->assertEquals([24, 34, 44, 54, 64, 74, 84], $nodes->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelationAndSelf()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $nodes = Node::has('ancestorsAndSelf', '>', 2)->get();

        $this->assertEquals([24, 54, 64, 74, 84], $nodes->pluck('id')->all());
    }

    public function testWithSumForSelfRelation()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $user = Node::withSum('ancestors', 'pivot_weight')->find(54);

        $this->assertEquals(1 + 4 + 5 + 10 + 11, $user->ancestors_sum_pivot_weight);
    }

    public function testWithSumForSelfRelationAndSelf()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $user = Node::withSum('ancestorsAndSelf', 'pivot_weight')->find(54);

        $this->assertEquals(1 + 4 + 5 + 10 + 11, $user->ancestors_and_self_sum_pivot_weight);
    }

    public function testDelete()
    {
        if ($this->database === 'mariadb') {
            $this->markTestSkipped();
        }

        $affected = Node::find(54)->ancestors()->delete();

        $this->assertEquals(4, $affected);
        $this->assertNotNull(Node::withTrashed()->find(24)->deleted_at);
        $this->assertNull(Node::find(74)->deleted_at);
    }
}

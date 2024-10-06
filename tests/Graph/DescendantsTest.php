<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Graph;

use PHPUnit\Framework\Attributes\DataProvider;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Graph\Descendants;
use Staudenmeir\LaravelAdjacencyList\Tests\Graph\Models\Node;
use Staudenmeir\LaravelAdjacencyList\Tests\Graph\Models\NodeWithCycleDetection;
use Staudenmeir\LaravelAdjacencyList\Tests\Graph\Models\NodeWithCycleDetectionAndStart;

class DescendantsTest extends TestCase
{
    public function testLazyLoading(): void
    {
        $descendants = Node::find(2)->descendants()->orderBy('depth')->get();

        $this->assertEquals([5, 7, 8, 8], $descendants->pluck('id')->all());
        $this->assertEquals([1, 2, 2, 3], $descendants->pluck('depth')->all());
        $this->assertEquals(['5', '5.7', '5.8', '5.7.8'], $descendants->pluck('path')->all());
        $this->assertEquals(
            ['node-5', 'node-5/node-7', 'node-5/node-8', 'node-5/node-7/node-8'],
            $descendants->pluck('slug_path')->all()
        );
        $this->assertEquals(
            ['node-5', 'node-7/node-5', 'node-8/node-5', 'node-8/node-7/node-5'],
            $descendants->pluck('reverse_slug_path')->all()
        );
        $this->assertEquals(
            [
                'parent_id' => 2,
                'child_id' => 5,
                'label' => 'e',
                'weight' => 5,
                'value' => '123.456',
                'created_at' => $this->getFormattedTestNow()
            ],
            $descendants[0]->pivot->getAttributes()
        );
    }

    #[DataProvider(methodName: 'cycleDetectionClassProvider')]
    public function testLazyLoadingWithCycleDetection(string $class, array $exclusions)
    {
        if (in_array($this->connection, $exclusions)) {
            $this->markTestSkipped();
        }

        $this->seedCycle();

        $descendants = $class::find(12)->descendants;

        $this->assertEquals([13, 14, 12], $descendants->pluck('id')->all());
        $this->assertEquals([1, 2, 3], $descendants->pluck('depth')->all());
    }

    #[DataProvider(methodName: 'cycleDetectionAndStartClassProvider')]
    public function testLazyLoadingWithCycleDetectionAndStart(string $class, array $exclusions)
    {
        if (in_array($this->connection, $exclusions)) {
            $this->markTestSkipped();
        }

        $this->seedCycle();

        $descendants = $class::find(12)->descendants;

        $this->assertEquals([13, 14, 12, 13], $descendants->pluck('id')->all());
        $this->assertEquals([1, 2, 3, 4], $descendants->pluck('depth')->all());
        $this->assertEquals([0, 0, 0, 1], $descendants->pluck('is_cycle')->all());
    }

    public function testLazyLoadingAndSelf(): void
    {
        if (in_array($this->connection, ['sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $descendantsAndSelf = Node::find(2)->descendantsAndSelf;

        $this->assertEquals([2, 5, 7, 8, 8], $descendantsAndSelf->pluck('id')->all());
        $this->assertEquals([0, 1, 2, 2, 3], $descendantsAndSelf->pluck('depth')->all());
        $this->assertEquals(
            ['2', '2.5', '2.5.7', '2.5.8', '2.5.7.8'],
            $descendantsAndSelf->pluck('path')->all()
        );
        $this->assertEquals(
            ['node-2', 'node-2/node-5', 'node-2/node-5/node-7', 'node-2/node-5/node-8', 'node-2/node-5/node-7/node-8'],
            $descendantsAndSelf->pluck('slug_path')->all()
        );
        $this->assertEquals(
            [
                'parent_id' => null,
                'child_id' => null,
                'label' => null,
                'weight' => null,
                'value' => null,
                'created_at' => null
            ],
            $descendantsAndSelf[0]->pivot->getAttributes()
        );
        $this->assertEquals(
            [
                'parent_id' => 2,
                'child_id' => 5,
                'label' => 'e',
                'weight' => 5,
                'value' => '123.456',
                'created_at' => $this->getFormattedTestNow()
            ],
            $descendantsAndSelf[1]->pivot->getAttributes()
        );
    }

    public function testLazyLoadingAndSelfWithCycleDetection(): void
    {
        if (in_array($this->connection, ['sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $this->seedCycle();

        $descendantsAndSelf = NodeWithCycleDetection::find(12)->descendantsAndSelf;

        $this->assertEquals([12, 13, 14], $descendantsAndSelf->pluck('id')->all());
        $this->assertEquals([0, 1, 2], $descendantsAndSelf->pluck('depth')->all());
    }

    public function testLazyLoadingAndSelfWithCycleDetectionAndStart(): void
    {
        if (in_array($this->connection, ['sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $this->seedCycle();

        $descendantsAndSelf = NodeWithCycleDetectionAndStart::find(12)->descendantsAndSelf;

        $this->assertEquals([12, 13, 14, 12], $descendantsAndSelf->pluck('id')->all());
        $this->assertEquals([0, 1, 2, 3], $descendantsAndSelf->pluck('depth')->all());
        $this->assertEquals([0, 0, 0, 1], $descendantsAndSelf->pluck('is_cycle')->all());
    }

    public function testEagerLoading(): void
    {
        $nodes = Node::with([
            'descendants' => fn (Descendants $query) => $query->orderBy('id')->orderBy('depth'),
        ])->get();

        $this->assertEquals(
            [2, 3, 4, 5, 5, 6, 7, 7, 8, 8, 8, 8],
            $nodes[0]->descendants->pluck('id')->all()
        );
        $this->assertEquals(
            [5, 7, 8, 8],
            $nodes[1]->descendants->pluck('id')->all()
        );
        $this->assertEquals([1, 2, 2, 3], $nodes[1]->descendants->pluck('depth')->all());
        $this->assertEquals(
            ['5', '5.7', '5.8', '5.7.8'],
            $nodes[1]->descendants->pluck('path')->all()
        );
        $this->assertEquals(
            [
                'parent_id' => 2,
                'child_id' => 5,
                'label' => 'e',
                'weight' => 5,
                'value' => '123.456',
                'created_at' => $this->getFormattedTestNow()
            ],
            $nodes[1]->descendants[0]->pivot->getAttributes()
        );
    }

    #[DataProvider(methodName: 'cycleDetectionClassProvider')]
    public function testEagerLoadingWithCycleDetection(string $class, array $exclusions)
    {
        if (in_array($this->connection, $exclusions)) {
            $this->markTestSkipped();
        }

        $this->seedCycle();

        $nodes = $class::with([
            'descendants' => fn (Descendants $query) => $query->orderBy('depth'),
        ])->findMany([12, 13, 14]);

        $this->assertEquals([13, 14, 12], $nodes[0]->descendants->pluck('id')->all());
        $this->assertEquals([1, 2, 3], $nodes[0]->descendants->pluck('depth')->all());
    }

    #[DataProvider(methodName: 'cycleDetectionAndStartClassProvider')]
    public function testEagerLoadingWithCycleDetectionAndStart(string $class, array $exclusions)
    {
        if (in_array($this->connection, $exclusions)) {
            $this->markTestSkipped();
        }

        $this->seedCycle();

        $nodes = $class::with([
            'descendants' => fn (Descendants $query) => $query->orderBy('depth')
        ])->findMany([12, 13, 14]);

        $this->assertEquals([13, 14, 12, 13], $nodes[0]->descendants->pluck('id')->all());
        $this->assertEquals([1, 2, 3, 4], $nodes[0]->descendants->pluck('depth')->all());
        $this->assertEquals([0, 0, 0, 1], $nodes[0]->descendants->pluck('is_cycle')->all());
    }

    public function testEagerLoadingAndSelf(): void
    {
        if (in_array($this->connection, ['sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $nodes = Node::with([
            'descendantsAndSelf' => fn (Descendants $query) => $query->orderBy('id')->orderBy('depth'),
        ])->get();

        $this->assertEquals(
            [1, 2, 3, 4, 5, 5, 6, 7, 7, 8, 8, 8, 8],
            $nodes[0]->descendantsAndSelf->pluck('id')->all()
        );
        $this->assertEquals(
            [2, 5, 7, 8, 8],
            $nodes[1]->descendantsAndSelf->pluck('id')->all()
        );
        $this->assertEquals([0, 1, 2, 2, 3], $nodes[1]->descendantsAndSelf->pluck('depth')->all());
        $this->assertEquals(
            ['2', '2.5', '2.5.7', '2.5.8', '2.5.7.8'],
            $nodes[1]->descendantsAndSelf->pluck('path')->all()
        );
        $this->assertEquals(
            [
                'parent_id' => null,
                'child_id' => null,
                'label' => null,
                'weight' => null,
                'value' => null,
                'created_at' => null
            ],
            $nodes[1]->descendantsAndSelf[0]->pivot->getAttributes()
        );
        $this->assertEquals(
            [
                'parent_id' => 2,
                'child_id' => 5,
                'label' => 'e',
                'weight' => 5,
                'value' => '123.456',
                'created_at' => $this->getFormattedTestNow()
            ],
            $nodes[1]->descendantsAndSelf[1]->pivot->getAttributes()
        );
    }

    public function testEagerLoadingAndSelfWithCycleDetection(): void
    {
        if (in_array($this->connection, ['sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $this->seedCycle();

        $nodes = NodeWithCycleDetection::with('descendantsAndSelf')->findMany([12, 13, 14]);

        $this->assertEquals([12, 13, 14], $nodes[0]->descendantsAndSelf->pluck('id')->all());
        $this->assertEquals([0, 1, 2], $nodes[0]->descendantsAndSelf->pluck('depth')->all());
    }

    public function testEagerLoadingAndSelfWithCycleDetectionAndStart(): void
    {
        if (in_array($this->connection, ['sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $this->seedCycle();

        $nodes = NodeWithCycleDetectionAndStart::with('descendantsAndSelf')->findMany([12, 13, 14]);

        $this->assertEquals([12, 13, 14, 12], $nodes[0]->descendantsAndSelf->pluck('id')->all());
        $this->assertEquals([0, 1, 2, 3], $nodes[0]->descendantsAndSelf->pluck('depth')->all());
        $this->assertEquals([0, 0, 0, 1], $nodes[0]->descendantsAndSelf->pluck('is_cycle')->all());
    }

    public function testLazyEagerLoading(): void
    {
        $nodes = Node::all()->load([
            'descendants' => fn (Descendants $query) => $query->orderBy('id')->orderBy('depth')
        ]);

        $this->assertEquals(
            [2, 3, 4, 5, 5, 6, 7, 7, 8, 8, 8, 8],
            $nodes[0]->descendants->pluck('id')->all()
        );
        $this->assertEquals(
            [5, 7, 8, 8],
            $nodes[1]->descendants->pluck('id')->all()
        );
        $this->assertEquals([1, 2, 2, 3], $nodes[1]->descendants->pluck('depth')->all());
        $this->assertEquals(
            ['5', '5.7', '5.8', '5.7.8'],
            $nodes[1]->descendants->pluck('path')->all()
        );
        $this->assertEquals(
            [
                'parent_id' => 2,
                'child_id' => 5,
                'label' => 'e',
                'weight' => 5,
                'value' => '123.456',
                'created_at' => $this->getFormattedTestNow()
            ],
            $nodes[1]->descendants[0]->pivot->getAttributes()
        );
    }

    public function testLazyEagerLoadingAndSelf(): void
    {
        if (in_array($this->connection, ['sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $nodes = Node::all()->load([
            'descendantsAndSelf' => fn (Descendants $query) => $query->orderBy('id')->orderBy('depth')
        ]);

        $this->assertEquals(
            [1, 2, 3, 4, 5, 5, 6, 7, 7, 8, 8, 8, 8],
            $nodes[0]->descendantsAndSelf->pluck('id')->all()
        );
        $this->assertEquals(
            [2, 5, 7, 8, 8],
            $nodes[1]->descendantsAndSelf->pluck('id')->all()
        );
        $this->assertEquals([0, 1, 2, 2, 3], $nodes[1]->descendantsAndSelf->pluck('depth')->all());
        $this->assertEquals(
            ['2', '2.5', '2.5.7', '2.5.8', '2.5.7.8'],
            $nodes[1]->descendantsAndSelf->pluck('path')->all()
        );
        $this->assertEquals(
            [
                'parent_id' => null,
                'child_id' => null,
                'label' => null,
                'weight' => null,
                'value' => null,
                'created_at' => null
            ],
            $nodes[1]->descendantsAndSelf[0]->pivot->getAttributes()
        );
        $this->assertEquals(
            [
                'parent_id' => 2,
                'child_id' => 5,
                'label' => 'e',
                'weight' => 5,
                'value' => '123.456',
                'created_at' => $this->getFormattedTestNow()
            ],
            $nodes[1]->descendantsAndSelf[1]->pivot->getAttributes()
        );
    }

    public function testExistenceQuery(): void
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $ancestors = Node::find(5)->ancestors()->has('descendants', '>', 2)->get();

        $this->assertEquals([1, 2, 10, 1, 9], $ancestors->pluck('id')->all());
    }

    public function testExistenceQueryWithCycleDetection(): void
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $this->seedCycle();

        $ancestors = NodeWithCycleDetection::find(12)->ancestors()->has('descendants', '>', 2)->get();

        $this->assertEquals([14, 13, 12], $ancestors->pluck('id')->all());
    }

    public function testExistenceQueryWithCycleDetectionAndStart(): void
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $this->seedCycle();

        $ancestors = NodeWithCycleDetectionAndStart::find(12)->ancestors()->has('descendants', '>', 3)->get();

        $this->assertEquals([14, 13, 12, 14], $ancestors->pluck('id')->all());
    }

    public function testExistenceQueryAndSelf(): void
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $ancestors = Node::find(5)->ancestors()->has('descendantsAndSelf', '>', 2)->get();

        $this->assertEquals([1, 2, 10, 1, 9], $ancestors->pluck('id')->all());
    }

    public function testExistenceQueryAndSelfWithCycleDetection(): void
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $this->seedCycle();

        $ancestors = NodeWithCycleDetection::find(12)->ancestors()->has('descendantsAndSelf', '>', 2)->get();

        $this->assertEquals([14, 13, 12], $ancestors->pluck('id')->all());
    }

    public function testExistenceQueryAndSelfWithCycleDetectionAndStart(): void
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $this->seedCycle();

        $ancestors = NodeWithCycleDetectionAndStart::find(12)->ancestors()->has('descendantsAndSelf', '>', 2)->get();

        $this->assertEquals([14, 13, 12, 14], $ancestors->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelation(): void
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $nodes = Node::has('descendants')->get();

        $this->assertEquals([1, 2, 3, 5, 7, 9, 10], $nodes->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelationAndSelf(): void
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $nodes = Node::has('descendantsAndSelf', '>', 2)->get();

        $this->assertEquals([1, 2, 5, 9, 10], $nodes->pluck('id')->all());
    }

    public function testWithSumForSelfRelation(): void
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $node = Node::withSum('descendants', 'pivot_weight')->find(2);

        $this->assertEquals(5 + 7 + 8 + 9, $node->descendants_sum_pivot_weight);
    }

    public function testWithSumForSelfRelationAndSelf(): void
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $node = Node::withSum('descendantsAndSelf', 'pivot_weight')->find(2);

        $this->assertEquals(5 + 7 + 8 + 9, $node->descendants_and_self_sum_pivot_weight);
    }

    public function testDelete(): void
    {
        if (in_array($this->connection, ['mariadb', 'firebird'])) {
            $this->markTestSkipped();
        }

        $affected = Node::find(2)->descendants()->delete();

        $this->assertEquals(3, $affected);
        $this->assertNotNull(Node::withTrashed()->find(5)->deleted_at);
        $this->assertNull(Node::find(1)->deleted_at);
    }
}

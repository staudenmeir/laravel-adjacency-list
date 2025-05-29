<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Tree;

use Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Ancestors;
use Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models\User;
use Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models\UserWithCycleDetection;
use Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models\UserWithCycleDetectionAndStart;

class AncestorsTest extends TestCase
{
    public function testLazyLoading(): void
    {
        $ancestors = User::find(8)->ancestors()->orderBy('id')->get();

        $this->assertEquals([1, 2, 5], $ancestors->pluck('id')->all());
        $this->assertEquals([-3, -2, -1], $ancestors->pluck('depth')->all());
        $this->assertEquals(['5.2.1', '5.2', '5'], $ancestors->pluck('path')->all());
        $this->assertEquals(['user-5,/user-2/user-1', 'user-5,/user-2', 'user-5,'], $ancestors->pluck('slug_path')->all());
        $this->assertEquals(
            ['user-1/user-2/user-5,', 'user-2/user-5,', 'user-5,'],
            $ancestors->pluck('reverse_slug_path')->all()
        );
    }

    public function testLazyLoadingWithRoot(): void
    {
        $ancestors = User::find(1)->ancestors;

        $this->assertEmpty($ancestors);
    }

    public function testLazyLoadingWithCycleDetection(): void
    {
        $this->seedCycle();

        $ancestors = UserWithCycleDetection::find(13)->ancestors;

        $this->assertEquals([15, 14, 13], $ancestors->pluck('id')->all());
        $this->assertEquals([-1, -2, -3], $ancestors->pluck('depth')->all());
    }

    public function testLazyLoadingWithCycleDetectionAndStart(): void
    {
        $this->seedCycle();

        $ancestors = UserWithCycleDetectionAndStart::find(13)->ancestors()->orderByDesc('depth')->get();

        $this->assertEquals([15, 14, 13, 15], $ancestors->pluck('id')->all());
        $this->assertEquals([-1, -2, -3, -4], $ancestors->pluck('depth')->all());
        $this->assertEquals([0, 0, 0, 1], $ancestors->pluck('is_cycle')->all());
    }

    public function testLazyLoadingAndSelf(): void
    {
        $ancestorsAndSelf = User::find(8)->ancestorsAndSelf()->orderBy('id')->get();

        $this->assertEquals([1, 2, 5, 8], $ancestorsAndSelf->pluck('id')->all());
        $this->assertEquals([-3, -2, -1, 0], $ancestorsAndSelf->pluck('depth')->all());
        $this->assertEquals(['8.5.2.1', '8.5.2', '8.5', '8', ], $ancestorsAndSelf->pluck('path')->all());
        $this->assertEquals(
            ['user-8/user-5,/user-2/user-1', 'user-8/user-5,/user-2', 'user-8/user-5,', 'user-8'],
            $ancestorsAndSelf->pluck('slug_path')->all()
        );
    }

    public function testLazyLoadingAndSelfWithRoot(): void
    {
        $ancestorsAndSelf = User::find(1)->ancestorsAndSelf;

        $this->assertEquals([1], $ancestorsAndSelf->pluck('id')->all());
    }

    public function testEagerLoading(): void
    {
        $users = User::with([
            'ancestors' => fn (Ancestors $query) => $query->orderBy('id'),
        ])->orderBy('id')->get();

        $this->assertEquals([], $users[0]->ancestors->pluck('id')->all());
        $this->assertEquals([1], $users[1]->ancestors->pluck('id')->all());
        $this->assertEquals([1, 2, 5], $users[7]->ancestors->pluck('id')->all());
        $this->assertEquals([-3, -2, -1], $users[7]->ancestors->pluck('depth')->all());
        $this->assertEquals(['5.2.1', '5.2', '5'], $users[7]->ancestors->pluck('path')->all());
    }

    public function testEagerLoadingWithCycleDetection(): void
    {
        $this->seedCycle();

        $users = UserWithCycleDetection::with([
            'ancestors' => fn (Ancestors $query) => $query->orderByDesc('depth'),
        ])->orderBy('id')->findMany([13, 14, 15]);

        $this->assertEquals([15, 14, 13], $users[0]->ancestors->pluck('id')->all());
        $this->assertEquals([-1, -2, -3], $users[0]->ancestors->pluck('depth')->all());
    }

    public function testEagerLoadingWithCycleDetectionAndStart(): void
    {
        $this->seedCycle();

        $users = UserWithCycleDetectionAndStart::with([
            'ancestors' => fn (Ancestors $query) => $query->orderByDesc('depth'),
        ])->orderBy('id')->findMany([13, 14, 15]);

        $this->assertEquals([15, 14, 13, 15], $users[0]->ancestors->pluck('id')->all());
        $this->assertEquals([-1, -2, -3, -4], $users[0]->ancestors->pluck('depth')->all());
        $this->assertEquals([0, 0, 0, 1], $users[0]->ancestors->pluck('is_cycle')->all());
    }

    public function testEagerLoadingAndSelf(): void
    {
        $users = User::with([
            'ancestorsAndSelf' => fn (Ancestors $query) => $query->orderBy('id'),
        ])->orderBy('id')->get();

        $this->assertEquals([1], $users[0]->ancestorsAndSelf->pluck('id')->all());
        $this->assertEquals([1, 2], $users[1]->ancestorsAndSelf->pluck('id')->all());
        $this->assertEquals([1, 2, 5, 8], $users[7]->ancestorsAndSelf->pluck('id')->all());
        $this->assertEquals([-3, -2, -1, 0], $users[7]->ancestorsAndSelf->pluck('depth')->all());
        $this->assertEquals(['8.5.2.1', '8.5.2', '8.5', '8'], $users[7]->ancestorsAndSelf->pluck('path')->all());
    }

    public function testLazyEagerLoading(): void
    {
        $users = User::orderBy('id')->get()->load([
            'ancestors' => fn (Ancestors $query) => $query->orderBy('id'),
        ]);

        $this->assertEquals([], $users[0]->ancestors->pluck('id')->all());
        $this->assertEquals([1], $users[1]->ancestors->pluck('id')->all());
        $this->assertEquals([1, 2, 5], $users[7]->ancestors->pluck('id')->all());
        $this->assertEquals([-3, -2, -1], $users[7]->ancestors->pluck('depth')->all());
        $this->assertEquals(['5.2.1', '5.2', '5'], $users[7]->ancestors->pluck('path')->all());
    }

    public function testLazyEagerLoadingAndSelf(): void
    {
        $users = User::orderBy('id')->get()->load([
            'ancestorsAndSelf' => fn (Ancestors $query) => $query->orderBy('id'),
        ]);

        $this->assertEquals([1], $users[0]->ancestorsAndSelf->pluck('id')->all());
        $this->assertEquals([1, 2], $users[1]->ancestorsAndSelf->pluck('id')->all());
        $this->assertEquals([1, 2, 5, 8], $users[7]->ancestorsAndSelf->pluck('id')->all());
        $this->assertEquals([-3, -2, -1, 0], $users[7]->ancestorsAndSelf->pluck('depth')->all());
        $this->assertEquals(['8.5.2.1', '8.5.2', '8.5', '8'], $users[7]->ancestorsAndSelf->pluck('path')->all());
    }

    public function testExistenceQuery(): void
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'singlestore', 'firebird'])) {
            $this->markTestSkipped();
        }

        $descendants = User::first()->descendants()->has('ancestors', '>', 2)->get();

        $this->assertEquals([8, 9], $descendants->pluck('id')->all());
    }

    public function testExistenceQueryAndSelf(): void
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'singlestore', 'firebird'])) {
            $this->markTestSkipped();
        }

        $descendants = User::first()->descendants()->has('ancestorsAndSelf', '>', 2)->get();

        $this->assertEquals([5, 6, 7, 8, 9], $descendants->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelation(): void
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'singlestore', 'firebird'])) {
            $this->markTestSkipped();
        }

        $users = User::has('ancestors')->get();

        $this->assertEquals([2, 3, 4, 5, 6, 7, 8, 9, 12], $users->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelationAndSelf(): void
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'singlestore', 'firebird'])) {
            $this->markTestSkipped();
        }

        $users = User::has('ancestorsAndSelf', '>', 2)->get();

        $this->assertEquals([5, 6, 7, 8, 9], $users->pluck('id')->all());
    }

    public function testWithSumForSelfRelation(): void
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'singlestore', 'firebird'])) {
            $this->markTestSkipped();
        }

        $user = User::withSum('ancestors', 'followers')->find(8);

        $this->assertEquals(3, $user->ancestors_sum_followers);
    }

    public function testWithSumForSelfRelationAndSelf(): void
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'singlestore', 'firebird'])) {
            $this->markTestSkipped();
        }

        $user = User::withSum('ancestorsAndSelf', 'followers')->find(8);

        $this->assertEquals(4, $user->ancestors_and_self_sum_followers);
    }

    public function testUpdate(): void
    {
        if (in_array($this->connection, ['mariadb', 'singlestore', 'firebird'])) {
            $this->markTestSkipped();
        }

        $affected = User::find(8)->ancestors()->update(['followers' => 2]);

        $this->assertEquals(3, $affected);
        $this->assertEquals(2, User::find(2)->followers);
        $this->assertEquals(1, User::find(3)->followers);
    }
}

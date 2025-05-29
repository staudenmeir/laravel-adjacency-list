<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Tree;

use Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Descendants;
use Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models\User;
use Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models\UserWithCycleDetection;
use Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models\UserWithCycleDetectionAndStart;

class DescendantsTest extends TestCase
{
    public function testLazyLoading(): void
    {
        $descendants = User::find(2)->descendants;

        $this->assertEquals([5, 8], $descendants->pluck('id')->all());
        $this->assertEquals([1, 2], $descendants->pluck('depth')->all());
        $this->assertEquals(['5', '5.8'], $descendants->pluck('path')->all());
        $this->assertEquals(['user-5,', 'user-5,/user-8'], $descendants->pluck('slug_path')->all());
        $this->assertEquals(['user-5,', 'user-8/user-5,'], $descendants->pluck('reverse_slug_path')->all());
    }

    public function testLazyLoadingWithCycleDetection(): void
    {
        $this->seedCycle();

        $descendants = UserWithCycleDetection::find(13)->descendants()->orderBy('depth')->get();

        $this->assertEquals([14, 15, 13], $descendants->pluck('id')->all());
        $this->assertEquals([1, 2, 3], $descendants->pluck('depth')->all());
    }

    public function testLazyLoadingWithCycleDetectionAndStart(): void
    {
        $this->seedCycle();

        $descendants = UserWithCycleDetectionAndStart::find(13)->descendants()->orderBy('depth')->get();

        $this->assertEquals([14, 15, 13, 14], $descendants->pluck('id')->all());
        $this->assertEquals([1, 2, 3, 4], $descendants->pluck('depth')->all());
        $this->assertEquals([0, 0, 0, 1], $descendants->pluck('is_cycle')->all());
    }

    public function testLazyLoadingAndSelf(): void
    {
        $descendantsAndSelf = User::find(2)->descendantsAndSelf()->orderBy('id')->get();

        $this->assertEquals([2, 5, 8], $descendantsAndSelf->pluck('id')->all());
        $this->assertEquals([0, 1, 2], $descendantsAndSelf->pluck('depth')->all());
        $this->assertEquals(['2', '2.5', '2.5.8'], $descendantsAndSelf->pluck('path')->all());
        $this->assertEquals(
            ['user-2', 'user-2/user-5,', 'user-2/user-5,/user-8'],
            $descendantsAndSelf->pluck('slug_path')->all()
        );
    }

    public function testLazyLoadingWithoutParentKey(): void
    {
        $descendants = (new User())->descendants()->get();

        $this->assertEmpty($descendants);
    }

    public function testEagerLoading(): void
    {
        $users = User::with([
            'descendants' => fn (Descendants $query) => $query->orderBy('id'),
        ])->orderBy('id')->get();

        $this->assertEquals([2, 3, 4, 5, 6, 7, 8, 9], $users[0]->descendants->pluck('id')->all());
        $this->assertEquals([12], $users[9]->descendants->pluck('id')->all());
        $this->assertEquals([], $users[10]->descendants->pluck('id')->all());
        $this->assertEquals([1, 1, 1, 2, 2, 2, 3, 3], $users[0]->descendants->pluck('depth')->all());
        $this->assertEquals(['2', '3', '4', '2.5', '3.6', '4.7', '2.5.8', '3.6.9'], $users[0]->descendants->pluck('path')->all());
    }

    public function testEagerLoadingWithCycleDetection(): void
    {
        $this->seedCycle();

        $users = UserWithCycleDetection::with([
            'descendants' => fn (Descendants $query) => $query->orderBy('depth'),
        ])->orderBy('id')->findMany([13, 14, 15]);

        $this->assertEquals([14, 15, 13], $users[0]->descendants->pluck('id')->all());
        $this->assertEquals([1, 2, 3], $users[0]->descendants->pluck('depth')->all());
    }

    public function testEagerLoadingWithCycleDetectionAndStart(): void
    {
        $this->seedCycle();

        $users = UserWithCycleDetectionAndStart::with([
            'descendants' => fn (Descendants $query) => $query->orderBy('depth'),
        ])->orderBy('id')->findMany([13, 14, 15]);

        $this->assertEquals([14, 15, 13, 14], $users[0]->descendants->pluck('id')->all());
        $this->assertEquals([1, 2, 3, 4], $users[0]->descendants->pluck('depth')->all());
        $this->assertEquals([0, 0, 0, 1], $users[0]->descendants->pluck('is_cycle')->all());
    }

    public function testEagerLoadingAndSelf(): void
    {
        $users = User::with([
            'descendantsAndSelf' => fn (Descendants $query) => $query->orderBy('id'),
        ])->orderBy('id')->get();

        $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9], $users[0]->descendantsAndSelf->pluck('id')->all());
        $this->assertEquals([11, 12], $users[9]->descendantsAndSelf->pluck('id')->all());
        $this->assertEquals([12], $users[10]->descendantsAndSelf->pluck('id')->all());
        $this->assertEquals([0, 1, 1, 1, 2, 2, 2, 3, 3], $users[0]->descendantsAndSelf->pluck('depth')->all());
        $this->assertEquals(['1', '1.2', '1.3', '1.4', '1.2.5', '1.3.6', '1.4.7', '1.2.5.8', '1.3.6.9'], $users[0]->descendantsAndSelf->pluck('path')->all());
    }

    public function testLazyEagerLoading(): void
    {
        $users = User::orderBy('id')->get()->load([
            'descendants' => fn (Descendants $query) => $query->orderBy('id'),
        ]);

        $this->assertEquals([2, 3, 4, 5, 6, 7, 8, 9], $users[0]->descendants->pluck('id')->all());
        $this->assertEquals([12], $users[9]->descendants->pluck('id')->all());
        $this->assertEquals([], $users[10]->descendants->pluck('id')->all());
        $this->assertEquals([1, 1, 1, 2, 2, 2, 3, 3], $users[0]->descendants->pluck('depth')->all());
        $this->assertEquals(['2', '3', '4', '2.5', '3.6', '4.7', '2.5.8', '3.6.9'], $users[0]->descendants->pluck('path')->all());
    }

    public function testLazyEagerLoadingAndSelf(): void
    {
        $users = User::orderBy('id')->get()->load([
            'descendantsAndSelf' => fn (Descendants $query) => $query->orderBy('id'),
        ]);

        $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9], $users[0]->descendantsAndSelf->pluck('id')->all());
        $this->assertEquals([11, 12], $users[9]->descendantsAndSelf->pluck('id')->all());
        $this->assertEquals([12], $users[10]->descendantsAndSelf->pluck('id')->all());
        $this->assertEquals([0, 1, 1, 1, 2, 2, 2, 3, 3], $users[0]->descendantsAndSelf->pluck('depth')->all());
        $this->assertEquals(['1', '1.2', '1.3', '1.4', '1.2.5', '1.3.6', '1.4.7', '1.2.5.8', '1.3.6.9'], $users[0]->descendantsAndSelf->pluck('path')->all());
    }

    public function testExistenceQuery(): void
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'singlestore', 'firebird'])) {
            $this->markTestSkipped();
        }

        $ancestors = User::find(8)->ancestors()->has('descendants', '>', 2)->get();

        $this->assertEquals([1], $ancestors->pluck('id')->all());
    }

    public function testExistenceQueryAndSelf(): void
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'singlestore', 'firebird'])) {
            $this->markTestSkipped();
        }

        $ancestors = User::find(8)->ancestors()->has('descendantsAndSelf', '>', 2)->get();

        $this->assertEquals([2, 1], $ancestors->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelation(): void
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'singlestore', 'firebird'])) {
            $this->markTestSkipped();
        }

        $users = User::has('descendants')->get();

        $this->assertEquals([1, 2, 3, 4, 5, 6, 11], $users->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelationAndSelf(): void
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'singlestore', 'firebird'])) {
            $this->markTestSkipped();
        }

        $users = User::has('descendantsAndSelf', '>', 2)->get();

        $this->assertEquals([1, 2, 3], $users->pluck('id')->all());
    }

    public function testWithSumForSelfRelation(): void
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'singlestore', 'firebird'])) {
            $this->markTestSkipped();
        }

        $user = User::withSum('descendants', 'followers')->find(2);

        $this->assertEquals(2, $user->descendants_sum_followers);
    }

    public function testWithSumForSelfRelationAndSelf(): void
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'singlestore', 'firebird'])) {
            $this->markTestSkipped();
        }

        $user = User::withSum('descendantsAndSelf', 'followers')->find(2);

        $this->assertEquals(3, $user->descendants_and_self_sum_followers);
    }

    public function testDelete(): void
    {
        if (in_array($this->connection, ['mariadb', 'singlestore', 'firebird'])) {
            $this->markTestSkipped();
        }

        $affected = User::find(2)->descendants()->delete();

        $this->assertEquals(2, $affected);
        $this->assertNotNull(User::withTrashed()->find(5)->deleted_at);
        $this->assertNull(User::find(3)->deleted_at);
    }

    public function testForceDelete(): void
    {
        if (in_array($this->connection, ['mariadb', 'singlestore', 'firebird'])) {
            $this->markTestSkipped();
        }

        $affected = User::find(2)->descendants()->forceDelete();

        $this->assertEquals(2, $affected);
        $this->assertNull(User::withTrashed()->find(5));
        $this->assertNull(User::find(3)->deleted_at);
    }
}

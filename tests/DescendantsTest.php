<?php

namespace Tests;

use Illuminate\Database\Capsule\Manager as DB;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Descendants;
use Tests\Models\User;

class DescendantsTest extends TestCase
{
    public function testLazyLoading()
    {
        $descendants = User::find(2)->descendants;

        $this->assertEquals([5, 8], $descendants->pluck('id')->all());
        $this->assertEquals([1, 2], $descendants->pluck('depth')->all());
        $this->assertEquals(['5', '5.8'], $descendants->pluck('path')->all());
        $this->assertEquals(['user-5', 'user-5/user-8'], $descendants->pluck('slug_path')->all());
    }

    public function testLazyLoadingAndSelf()
    {
        $descendantsAndSelf = User::find(2)->descendantsAndSelf;

        $this->assertEquals([2, 5, 8], $descendantsAndSelf->pluck('id')->all());
        $this->assertEquals([0, 1, 2], $descendantsAndSelf->pluck('depth')->all());
        $this->assertEquals(['2', '2.5', '2.5.8'], $descendantsAndSelf->pluck('path')->all());
    }

    public function testLazyLoadingWithoutParentKey()
    {
        $descendants = (new User())->descendants()->get();

        $this->assertEmpty($descendants);
    }

    public function testEagerLoading()
    {
        $users = User::with(['descendants' => function (Descendants $query) {
            $query->orderBy('id');
        }])->get();

        $this->assertEquals([2, 3, 4, 5, 6, 7, 8, 9], $users[0]->descendants->pluck('id')->all());
        $this->assertEquals([12], $users[9]->descendants->pluck('id')->all());
        $this->assertEquals([], $users[10]->descendants->pluck('id')->all());
        $this->assertEquals([1, 1, 1, 2, 2, 2, 3, 3], $users[0]->descendants->pluck('depth')->all());
        $this->assertEquals(['2', '3', '4', '2.5', '3.6', '4.7', '2.5.8', '3.6.9'], $users[0]->descendants->pluck('path')->all());
    }

    public function testEagerLoadingAndSelf()
    {
        $users = User::with(['descendantsAndSelf' => function (Descendants $query) {
            $query->orderBy('id');
        }])->get();

        $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9], $users[0]->descendantsAndSelf->pluck('id')->all());
        $this->assertEquals([11, 12], $users[9]->descendantsAndSelf->pluck('id')->all());
        $this->assertEquals([12], $users[10]->descendantsAndSelf->pluck('id')->all());
        $this->assertEquals([0, 1, 1, 1, 2, 2, 2, 3, 3], $users[0]->descendantsAndSelf->pluck('depth')->all());
        $this->assertEquals(['1', '1.2', '1.3', '1.4', '1.2.5', '1.3.6', '1.4.7', '1.2.5.8', '1.3.6.9'], $users[0]->descendantsAndSelf->pluck('path')->all());
    }

    public function testLazyEagerLoading()
    {
        $users = User::all()->load(['descendants' => function (Descendants $query) {
            $query->orderBy('id');
        }]);

        $this->assertEquals([2, 3, 4, 5, 6, 7, 8, 9], $users[0]->descendants->pluck('id')->all());
        $this->assertEquals([12], $users[9]->descendants->pluck('id')->all());
        $this->assertEquals([], $users[10]->descendants->pluck('id')->all());
        $this->assertEquals([1, 1, 1, 2, 2, 2, 3, 3], $users[0]->descendants->pluck('depth')->all());
        $this->assertEquals(['2', '3', '4', '2.5', '3.6', '4.7', '2.5.8', '3.6.9'], $users[0]->descendants->pluck('path')->all());
    }

    public function testLazyEagerLoadingAndSelf()
    {
        $users = User::all()->load(['descendantsAndSelf' => function (Descendants $query) {
            $query->orderBy('id');
        }]);

        $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9], $users[0]->descendantsAndSelf->pluck('id')->all());
        $this->assertEquals([11, 12], $users[9]->descendantsAndSelf->pluck('id')->all());
        $this->assertEquals([12], $users[10]->descendantsAndSelf->pluck('id')->all());
        $this->assertEquals([0, 1, 1, 1, 2, 2, 2, 3, 3], $users[0]->descendantsAndSelf->pluck('depth')->all());
        $this->assertEquals(['1', '1.2', '1.3', '1.4', '1.2.5', '1.3.6', '1.4.7', '1.2.5.8', '1.3.6.9'], $users[0]->descendantsAndSelf->pluck('path')->all());
    }

    public function testExistenceQuery()
    {
        if (DB::connection()->getDriverName() === 'sqlsrv') {
            $this->markTestSkipped();
        }

        $ancestors = User::find(8)->ancestors()->has('descendants', '>', 2)->get();

        $this->assertEquals([1], $ancestors->pluck('id')->all());
    }

    public function testExistenceQueryAndSelf()
    {
        if (DB::connection()->getDriverName() === 'sqlsrv') {
            $this->markTestSkipped();
        }

        $ancestors = User::find(8)->ancestors()->has('descendantsAndSelf', '>', 2)->get();

        $this->assertEquals([2, 1], $ancestors->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelation()
    {
        if (DB::connection()->getDriverName() === 'sqlsrv') {
            $this->markTestSkipped();
        }

        $users = User::has('descendants')->get();

        $this->assertEquals([1, 2, 3, 4, 5, 6, 11], $users->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelationAndSelf()
    {
        if (DB::connection()->getDriverName() === 'sqlsrv') {
            $this->markTestSkipped();
        }

        $users = User::has('descendantsAndSelf', '>', 2)->get();

        $this->assertEquals([1, 2, 3], $users->pluck('id')->all());
    }

    public function testUpdate()
    {
        $affected = User::find(1)->descendants()->update(['parent_id' => 12]);

        $this->assertEquals(8, $affected);
        $this->assertEquals(12, User::find(8)->parent_id);
        $this->assertEquals(11, User::find(12)->parent_id);
    }
}

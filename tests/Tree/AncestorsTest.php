<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Tree;

use Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models\User;

class AncestorsTest extends TestCase
{
    public function testLazyLoading()
    {
        $ancestors = User::find(8)->ancestors;

        $this->assertEquals([5, 2, 1], $ancestors->pluck('id')->all());
        $this->assertEquals([-1, -2, -3], $ancestors->pluck('depth')->all());
        $this->assertEquals(['5', '5.2', '5.2.1'], $ancestors->pluck('path')->all());
        $this->assertEquals(['user-5', 'user-5/user-2', 'user-5/user-2/user-1'], $ancestors->pluck('slug_path')->all());
        $this->assertEquals(
            ['user-5', 'user-2/user-5', 'user-1/user-2/user-5'],
            $ancestors->pluck('reverse_slug_path')->all()
        );
    }

    public function testLazyLoadingWithRoot()
    {
        $ancestors = User::find(1)->ancestors;

        $this->assertEmpty($ancestors);
    }

    public function testLazyLoadingAndSelf()
    {
        $ancestorsAndSelf = User::find(8)->ancestorsAndSelf;

        $this->assertEquals([8, 5, 2, 1], $ancestorsAndSelf->pluck('id')->all());
        $this->assertEquals([0, -1, -2, -3], $ancestorsAndSelf->pluck('depth')->all());
        $this->assertEquals(['8', '8.5', '8.5.2', '8.5.2.1'], $ancestorsAndSelf->pluck('path')->all());
        $this->assertEquals(
            ['user-8', 'user-8/user-5', 'user-8/user-5/user-2', 'user-8/user-5/user-2/user-1'],
            $ancestorsAndSelf->pluck('slug_path')->all()
        );
    }

    public function testLazyLoadingAndSelfWithRoot()
    {
        $ancestorsAndSelf = User::find(1)->ancestorsAndSelf;

        $this->assertEquals([1], $ancestorsAndSelf->pluck('id')->all());
    }

    public function testEagerLoading()
    {
        $users = User::with('ancestors')->get();

        $this->assertEquals([], $users[0]->ancestors->pluck('id')->all());
        $this->assertEquals([1], $users[1]->ancestors->pluck('id')->all());
        $this->assertEquals([5, 2, 1], $users[7]->ancestors->pluck('id')->all());
        $this->assertEquals([-1, -2, -3], $users[7]->ancestors->pluck('depth')->all());
        $this->assertEquals(['5', '5.2', '5.2.1'], $users[7]->ancestors->pluck('path')->all());
    }

    public function testEagerLoadingAndSelf()
    {
        $users = User::with('ancestorsAndSelf')->get();

        $this->assertEquals([1], $users[0]->ancestorsAndSelf->pluck('id')->all());
        $this->assertEquals([2, 1], $users[1]->ancestorsAndSelf->pluck('id')->all());
        $this->assertEquals([8, 5, 2, 1], $users[7]->ancestorsAndSelf->pluck('id')->all());
        $this->assertEquals([0, -1, -2, -3], $users[7]->ancestorsAndSelf->pluck('depth')->all());
        $this->assertEquals(['8', '8.5', '8.5.2', '8.5.2.1'], $users[7]->ancestorsAndSelf->pluck('path')->all());
    }

    public function testLazyEagerLoading()
    {
        $users = User::all()->load('ancestors');

        $this->assertEquals([], $users[0]->ancestors->pluck('id')->all());
        $this->assertEquals([1], $users[1]->ancestors->pluck('id')->all());
        $this->assertEquals([5, 2, 1], $users[7]->ancestors->pluck('id')->all());
        $this->assertEquals([-1, -2, -3], $users[7]->ancestors->pluck('depth')->all());
        $this->assertEquals(['5', '5.2', '5.2.1'], $users[7]->ancestors->pluck('path')->all());
    }

    public function testLazyEagerLoadingAndSelf()
    {
        $users = User::all()->load('ancestorsAndSelf');

        $this->assertEquals([1], $users[0]->ancestorsAndSelf->pluck('id')->all());
        $this->assertEquals([2, 1], $users[1]->ancestorsAndSelf->pluck('id')->all());
        $this->assertEquals([8, 5, 2, 1], $users[7]->ancestorsAndSelf->pluck('id')->all());
        $this->assertEquals([0, -1, -2, -3], $users[7]->ancestorsAndSelf->pluck('depth')->all());
        $this->assertEquals(['8', '8.5', '8.5.2', '8.5.2.1'], $users[7]->ancestorsAndSelf->pluck('path')->all());
    }

    public function testExistenceQuery()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $descendants = User::first()->descendants()->has('ancestors', '>', 2)->get();

        $this->assertEquals([8, 9], $descendants->pluck('id')->all());
    }

    public function testExistenceQueryAndSelf()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $descendants = User::first()->descendants()->has('ancestorsAndSelf', '>', 2)->get();

        $this->assertEquals([5, 6, 7, 8, 9], $descendants->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelation()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $users = User::has('ancestors')->get();

        $this->assertEquals([2, 3, 4, 5, 6, 7, 8, 9, 12], $users->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelationAndSelf()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $users = User::has('ancestorsAndSelf', '>', 2)->get();

        $this->assertEquals([5, 6, 7, 8, 9], $users->pluck('id')->all());
    }

    public function testWithSumForSelfRelation()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $user = User::withSum('ancestors', 'followers')->find(8);

        $this->assertEquals(3, $user->ancestors_sum_followers);
    }

    public function testWithSumForSelfRelationAndSelf()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $user = User::withSum('ancestorsAndSelf', 'followers')->find(8);

        $this->assertEquals(4, $user->ancestors_and_self_sum_followers);
    }

    public function testUpdate()
    {
        if ($this->database === 'mariadb') {
            $this->markTestSkipped();
        }

        $affected = User::find(8)->ancestors()->update(['followers' => 2]);

        $this->assertEquals(3, $affected);
        $this->assertEquals(2, User::find(2)->followers);
        $this->assertEquals(1, User::find(3)->followers);
    }
}

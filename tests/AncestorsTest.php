<?php

namespace Tests;

use Tests\Models\User;

class AncestorsTest extends TestCase
{
    public function testLazyLoading()
    {
        $ancestors = User::find(8)->ancestors;

        $this->assertEquals([5, 2, 1], $ancestors->pluck('id')->all());
        $this->assertEquals([-1, -2, -3], $ancestors->pluck('depth')->all());
        $this->assertEquals(['5', '5.2', '5.2.1'], $ancestors->pluck('path')->all());
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
        $descendants = User::first()->descendants()->has('ancestors', '>', 2)->get();

        $this->assertEquals([8, 9], $descendants->pluck('id')->all());
    }

    public function testExistenceQueryAndSelf()
    {
        $descendants = User::first()->descendants()->has('ancestorsAndSelf', '>', 2)->get();

        $this->assertEquals([5, 6, 7, 8, 9], $descendants->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelation()
    {
        $users = User::has('ancestors')->get();

        $this->assertEquals([2, 3, 4, 5, 6, 7, 8, 9, 12], $users->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelationAndSelf()
    {
        $users = User::has('ancestorsAndSelf', '>', 2)->get();

        $this->assertEquals([5, 6, 7, 8, 9], $users->pluck('id')->all());
    }

    public function testUpdate()
    {
        $affected = User::find(8)->ancestors()->update(['parent_id' => 12]);

        $this->assertEquals(3, $affected);
        $this->assertEquals(12, User::find(2)->parent_id);
        $this->assertEquals(1, User::find(3)->parent_id);
    }
}

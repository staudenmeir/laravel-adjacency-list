<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests;

use Staudenmeir\LaravelAdjacencyList\Tests\Models\User;

class RootAncestorTest extends TestCase
{
    public function testLazyLoading()
    {
        $rootAncestor = User::find(8)->rootAncestor;

        $this->assertEquals(1, $rootAncestor->id);
        $this->assertEquals(-3, $rootAncestor->depth);
        $this->assertEquals('5.2.1', $rootAncestor->path);
    }

    public function testEagerLoading()
    {
        $users = User::with('rootAncestor')->get();

        $this->assertNull($users[0]->rootAncestor);
        $this->assertEquals(1, $users[7]->rootAncestor->id);
        $this->assertEquals(11, $users[10]->rootAncestor->id);
        $this->assertEquals(-3, $users[7]->rootAncestor->depth);
        $this->assertEquals('5.2.1', $users[7]->rootAncestor->path);
    }

    public function testLazyEagerLoading()
    {
        $users = User::all()->load('rootAncestor');

        $this->assertNull($users[0]->rootAncestor);
        $this->assertEquals(1, $users[7]->rootAncestor->id);
        $this->assertEquals(11, $users[10]->rootAncestor->id);
        $this->assertEquals(-3, $users[7]->rootAncestor->depth);
        $this->assertEquals('5.2.1', $users[7]->rootAncestor->path);
    }

    public function testExistenceQuery()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $descendants = User::first()->descendants()->has('rootAncestor')->get();

        $this->assertEquals([2, 3, 4, 5, 6, 7, 8, 9], $descendants->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelation()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $users = User::has('rootAncestor')->get();

        $this->assertEquals([2, 3, 4, 5, 6, 7, 8, 9, 12], $users->pluck('id')->all());
    }

    public function testUpdate()
    {
        if ($this->database === 'mariadb') {
            $this->markTestSkipped();
        }

        $affected = User::find(8)->rootAncestor()->update(['parent_id' => 12]);

        $this->assertEquals(1, $affected);
        $this->assertEquals(12, User::find(1)->parent_id);
        $this->assertEquals(1, User::find(2)->parent_id);
    }
}

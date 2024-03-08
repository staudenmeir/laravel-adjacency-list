<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Tree;

use Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models\User;

class RootAncestorOrSelfTest extends TestCase
{
    public function testLazyLoading()
    {
        $rootAncestorOrSelf = User::find(8)->rootAncestorOrSelf;

        $this->assertEquals(1, $rootAncestorOrSelf->id);
        $this->assertEquals(-3, $rootAncestorOrSelf->depth);
        $this->assertEquals('8.5.2.1', $rootAncestorOrSelf->path);
    }

    public function testEagerLoading()
    {
        $users = User::with('rootAncestorOrSelf')->orderBy('id')->get();

        $this->assertEquals(1, $users[0]->rootAncestorOrSelf->id);
        $this->assertEquals(1, $users[1]->rootAncestorOrSelf->id);
        $this->assertEquals(1, $users[7]->rootAncestorOrSelf->id);
        $this->assertEquals(11, $users[10]->rootAncestorOrSelf->id);
        $this->assertEquals(-3, $users[7]->rootAncestorOrSelf->depth);
        $this->assertEquals('8.5.2.1', $users[7]->rootAncestorOrSelf->path);
    }

    public function testLazyEagerLoading()
    {
        $users = User::orderBy('id')->get()->load('rootAncestorOrSelf');
        ;

        $this->assertEquals(1, $users[0]->rootAncestorOrSelf->id);
        $this->assertEquals(1, $users[1]->rootAncestorOrSelf->id);
        $this->assertEquals(1, $users[7]->rootAncestorOrSelf->id);
        $this->assertEquals(11, $users[10]->rootAncestorOrSelf->id);
        $this->assertEquals(-3, $users[7]->rootAncestorOrSelf->depth);
        $this->assertEquals('8.5.2.1', $users[7]->rootAncestorOrSelf->path);
    }

    public function testExistenceQuery()
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'singlestore', 'firebird'])) {
            $this->markTestSkipped();
        }

        $descendants = User::first()->descendants()->has('rootAncestorOrSelf')->get();

        $this->assertEquals([2, 3, 4, 5, 6, 7, 8, 9], $descendants->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelation()
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'singlestore', 'firebird'])) {
            $this->markTestSkipped();
        }

        $users = User::has('rootAncestorOrSelf')->get();

        $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9, 11, 12], $users->pluck('id')->all());
    }

    public function testUpdate()
    {
        if (in_array($this->connection, ['mariadb', 'singlestore', 'firebird'])) {
            $this->markTestSkipped();
        }

        $affected = User::find(1)->rootAncestorOrSelf()->update(['followers' => 2]);

        $this->assertEquals(1, $affected);
        $this->assertEquals(2, User::find(1)->followers);
    }
}

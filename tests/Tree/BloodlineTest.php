<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Tree;

use Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Bloodline;
use Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models\User;

class BloodlineTest extends TestCase
{
    public function testLazyLoading()
    {
        $bloodline = User::find(5)->bloodline()->breadthFirst()->get();

        $this->assertEquals([1, 2, 5, 8], $bloodline->pluck('id')->all());
        $this->assertEquals([-2, -1, 0, 1], $bloodline->pluck('depth')->all());
        $this->assertEquals(['5.2.1', '5.2', '5', '5.8'], $bloodline->pluck('path')->all());
        $this->assertEquals(['user-5/user-2/user-1', 'user-5/user-2', 'user-5', 'user-5/user-8'], $bloodline->pluck('slug_path')->all());
    }

    public function testEagerLoading()
    {
        $users = User::with([
            'bloodline' => fn (Bloodline $query) => $query->getQuery()->breadthFirst()->orderBy('id'),
        ])->orderBy('id')->get();

        $this->assertEquals(range(1, 9), $users[0]->bloodline->pluck('id')->all());
        $this->assertEquals([1, 2, 5, 8], $users[1]->bloodline->pluck('id')->all());
        $this->assertEquals([1, 2, 5, 8], $users[4]->bloodline->pluck('id')->all());
        $this->assertEquals(['5.2.1', '5.2', '5', '5.8'], $users[4]->bloodline->pluck('path')->all());
        $this->assertEquals(['user-5/user-2/user-1', 'user-5/user-2', 'user-5', 'user-5/user-8'], $users[4]->bloodline->pluck('slug_path')->all());
    }

    public function testLazyEagerLoading()
    {
        $users = User::orderBy('id')->get()->load([
            'bloodline' => fn (Bloodline $query) => $query->getQuery()->breadthFirst()->orderBy('id'),
        ]);

        $this->assertEquals(range(1, 9), $users[0]->bloodline->pluck('id')->all());
        $this->assertEquals([1, 2, 5, 8], $users[1]->bloodline->pluck('id')->all());
        $this->assertEquals([1, 2, 5, 8], $users[4]->bloodline->pluck('id')->all());
        $this->assertEquals(['5.2.1', '5.2', '5', '5.8'], $users[4]->bloodline->pluck('path')->all());
        $this->assertEquals(['user-5/user-2/user-1', 'user-5/user-2', 'user-5', 'user-5/user-8'], $users[4]->bloodline->pluck('slug_path')->all());
    }

    public function testExistenceQuery()
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'singlestore', 'firebird'])) {
            $this->markTestSkipped();
        }

        $descendants = User::first()->descendants()->has('bloodline', '<', 4)->get();

        $this->assertEquals([4, 7], $descendants->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelation()
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'singlestore', 'firebird'])) {
            $this->markTestSkipped();
        }

        $users = User::has('bloodline', '<', 4)->get();

        $this->assertEquals([4, 7, 11, 12], $users->pluck('id')->all());
    }

    public function testIncrement()
    {
        if (in_array($this->connection, ['mariadb', 'singlestore', 'firebird'])) {
            $this->markTestSkipped();
        }

        $affected = User::find(5)->bloodline()->increment('followers');

        $this->assertEquals(4, $affected);
        $this->assertEquals(2, User::find(1)->followers);
        $this->assertEquals(2, User::find(8)->followers);
        $this->assertEquals(1, User::find(3)->followers);
    }

    public function testDecrement()
    {
        if (in_array($this->connection, ['mariadb', 'singlestore', 'firebird'])) {
            $this->markTestSkipped();
        }

        $affected = User::find(5)->bloodline()->decrement('followers');

        $this->assertEquals(4, $affected);
        $this->assertEquals(0, User::find(1)->followers);
        $this->assertEquals(0, User::find(8)->followers);
        $this->assertEquals(1, User::find(3)->followers);
    }
}

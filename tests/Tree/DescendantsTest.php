<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Tree;

use Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Descendants;
use Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models\User;

class DescendantsTest extends TestCase
{
    public function testLazyLoading()
    {
        $descendants = User::find(2)->descendants;

        $this->assertEquals([5, 8], $descendants->pluck('id')->all());
        $this->assertEquals([1, 2], $descendants->pluck('depth')->all());
        $this->assertEquals(['5', '5.8'], $descendants->pluck('path')->all());
        $this->assertEquals(['user-5', 'user-5/user-8'], $descendants->pluck('slug_path')->all());
        $this->assertEquals(['user-5', 'user-8/user-5'], $descendants->pluck('reverse_slug_path')->all());
    }

    public function testLazyLoadingAndSelf()
    {
        $descendantsAndSelf = User::find(2)->descendantsAndSelf()->orderBy('id')->get();

        $this->assertEquals([2, 5, 8], $descendantsAndSelf->pluck('id')->all());
        $this->assertEquals([0, 1, 2], $descendantsAndSelf->pluck('depth')->all());
        $this->assertEquals(['2', '2.5', '2.5.8'], $descendantsAndSelf->pluck('path')->all());
        $this->assertEquals(
            ['user-2', 'user-2/user-5', 'user-2/user-5/user-8'],
            $descendantsAndSelf->pluck('slug_path')->all()
        );
    }

    public function testLazyLoadingWithoutParentKey()
    {
        $descendants = (new User())->descendants()->get();

        $this->assertEmpty($descendants);
    }

    public function testEagerLoading()
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

    public function testEagerLoadingAndSelf()
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

    public function testLazyEagerLoading()
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

    public function testLazyEagerLoadingAndSelf()
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

    public function testExistenceQuery()
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'singlestore', 'firebird'])) {
            $this->markTestSkipped();
        }

        $ancestors = User::find(8)->ancestors()->has('descendants', '>', 2)->get();

        $this->assertEquals([1], $ancestors->pluck('id')->all());
    }

    public function testExistenceQueryAndSelf()
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'singlestore', 'firebird'])) {
            $this->markTestSkipped();
        }

        $ancestors = User::find(8)->ancestors()->has('descendantsAndSelf', '>', 2)->get();

        $this->assertEquals([2, 1], $ancestors->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelation()
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'singlestore', 'firebird'])) {
            $this->markTestSkipped();
        }

        $users = User::has('descendants')->get();

        $this->assertEquals([1, 2, 3, 4, 5, 6, 11], $users->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelationAndSelf()
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'singlestore', 'firebird'])) {
            $this->markTestSkipped();
        }

        $users = User::has('descendantsAndSelf', '>', 2)->get();

        $this->assertEquals([1, 2, 3], $users->pluck('id')->all());
    }

    public function testWithSumForSelfRelation()
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'singlestore', 'firebird'])) {
            $this->markTestSkipped();
        }

        $user = User::withSum('descendants', 'followers')->find(2);

        // @phpstan-ignore property.notFound
        $this->assertEquals(2, $user->descendants_sum_followers);
    }

    public function testWithSumForSelfRelationAndSelf()
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'singlestore', 'firebird'])) {
            $this->markTestSkipped();
        }

        $user = User::withSum('descendantsAndSelf', 'followers')->find(2);

        // @phpstan-ignore property.notFound
        $this->assertEquals(3, $user->descendants_and_self_sum_followers);
    }

    public function testDelete()
    {
        if (in_array($this->connection, ['mariadb', 'singlestore', 'firebird'])) {
            $this->markTestSkipped();
        }

        $affected = User::find(2)->descendants()->delete();

        $this->assertEquals(2, $affected);
        $this->assertNotNull(User::withTrashed()->find(5)->deleted_at);
        $this->assertNull(User::find(3)->deleted_at);
    }

    public function testForceDelete()
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

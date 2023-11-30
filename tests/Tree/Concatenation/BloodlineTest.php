<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Tree\Concatenation;

use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models\Role;
use Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models\User;
use Staudenmeir\LaravelAdjacencyList\Tests\Tree\TestCase;

class BloodlineTest extends TestCase
{
    public function testLazyLoading()
    {
        $posts = User::find(5)->bloodlinePosts()->orderBy('id')->get();

        $this->assertEquals([10, 20, 50, 80], $posts->pluck('id')->all());
    }

    public function testLazyLoadingWithoutParentKey()
    {
        $posts = (new User())->bloodlinePosts()->get();

        $this->assertEmpty($posts);
    }

    public function testEagerLoading()
    {
        $users = User::with([
            'bloodlinePosts' => fn (HasManyDeep $query) => $query->orderBy('id'),
        ])->orderBy('id')->get();

        $this->assertEquals([10, 20, 30, 40, 50, 60, 70, 80], $users[0]->bloodlinePosts->pluck('id')->all());
        $this->assertEquals([10, 20, 50, 80], $users[1]->bloodlinePosts->pluck('id')->all());
        $this->assertEquals([10, 20, 50, 80], $users[4]->bloodlinePosts->pluck('id')->all());
    }

    public function testLazyEagerLoading()
    {
        $users = User::orderBy('id')->get()->load([
            'bloodlinePosts' => fn (HasManyDeep $query) => $query->orderBy('id'),
        ]);

        $this->assertEquals([10, 20, 30, 40, 50, 60, 70, 80], $users[0]->bloodlinePosts->pluck('id')->all());
        $this->assertEquals([10, 20, 50, 80], $users[1]->bloodlinePosts->pluck('id')->all());
        $this->assertEquals([10, 20, 50, 80], $users[4]->bloodlinePosts->pluck('id')->all());
    }

    public function testExistenceQuery()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv', 'singlestore'])) {
            $this->markTestSkipped();
        }

        $users = User::find(8)->ancestors()->has('bloodlinePosts', '=', 4)->get();

        $this->assertEquals([5, 2], $users->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelation()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv', 'singlestore'])) {
            $this->markTestSkipped();
        }

        $users = User::has('bloodlinePosts', '=', 4)->get();

        $this->assertEquals([2, 5, 8], $users->pluck('id')->all());
    }

    public function testUnsupportedPosition()
    {
        $this->expectExceptionMessage('Bloodline can only be at the beginning of deep relationships at the moment.');

        Role::find(11)->userBloodline;
    }
}

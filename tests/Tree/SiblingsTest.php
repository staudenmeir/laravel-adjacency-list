<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Tree;

use Illuminate\Database\Eloquent\Builder;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Siblings;
use Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models\User;

class SiblingsTest extends TestCase
{
    public function testLazyLoading()
    {
        $siblings = User::find(2)->siblings()->orderBy('id')->get();

        $this->assertEquals([3, 4], $siblings->pluck('id')->all());
    }

    public function testLazyLoadingWithRoot()
    {
        $siblings = User::find(1)->siblings;

        $this->assertEquals([11], $siblings->pluck('id')->all());
    }

    public function testLazyLoadingAndSelf()
    {
        $siblingsAndSelf = User::find(2)->siblingsAndSelf()->orderBy('id')->get();

        $this->assertEquals([2, 3, 4], $siblingsAndSelf->pluck('id')->all());
    }

    public function testLazyLoadingAndSelfWithRoot()
    {
        $siblingsAndSelf = User::find(1)->siblingsAndSelf()->orderBy('id')->get();

        $this->assertEquals([1, 11], $siblingsAndSelf->pluck('id')->all());
    }

    public function testLazyLoadingWithoutParentKey()
    {
        $siblings = (new User())->siblings;

        $this->assertEmpty($siblings);
    }

    public function testEagerLoading()
    {
        $users = User::with([
            'siblings' => fn (Siblings $query) => $query->orderBy('id'),
        ])->orderBy('id')->get();

        $this->assertEquals([11], $users[0]->siblings->pluck('id')->all());
        $this->assertEquals([3, 4], $users[1]->siblings->pluck('id')->all());
    }

    public function testEagerLoadingAndSelf()
    {
        $users = User::with([
            'siblingsAndSelf' => fn (Siblings $query) => $query->orderBy('id'),
        ])->orderBy('id')->get();

        $this->assertEquals([1, 11], $users[0]->siblingsAndSelf->pluck('id')->all());
        $this->assertEquals([2, 3, 4], $users[1]->siblingsAndSelf->pluck('id')->all());
    }

    public function testLazyEagerLoading()
    {
        $users = User::orderBy('id')->get()->load([
            'siblings' => fn (Siblings $query) => $query->orderBy('id'),
        ]);

        $this->assertEquals([11], $users[0]->siblings->pluck('id')->all());
        $this->assertEquals([3, 4], $users[1]->siblings->pluck('id')->all());
    }

    public function testLazyEagerLoadingAndSelf()
    {
        $users = User::orderBy('id')->get()->load([
            'siblingsAndSelf' => fn (Siblings $query) => $query->orderBy('id'),
        ]);

        $this->assertEquals([1, 11], $users[0]->siblingsAndSelf->pluck('id')->all());
        $this->assertEquals([2, 3, 4], $users[1]->siblingsAndSelf->pluck('id')->all());
    }

    public function testExistenceQuery()
    {
        if ($this->connection === 'singlestore') {
            $this->markTestSkipped();
        }

        $users = User::tree()->has('siblings', '>', 1)->get();

        $this->assertEquals([2, 3, 4], $users->pluck('id')->all());
    }

    public function testExistenceQueryAndSelf()
    {
        if ($this->connection === 'singlestore') {
            $this->markTestSkipped();
        }

        $descendants = User::first()->descendants()->has('siblingsAndSelf', '>', 2)->get();

        $this->assertEquals([2, 3, 4], $descendants->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelation()
    {
        $users = User::has('siblings')->orderBy('id')->get();

        $this->assertEquals([1, 2, 3, 4, 11], $users->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelationAndSelf()
    {
        $users = User::whereHas(
            'siblingsAndSelf',
            fn (Builder $query) => $query->where('id', '<', 5)
        )->orderBy('id')->get();

        $this->assertEquals([1, 2, 3, 4, 11], $users->pluck('id')->all());
    }
}

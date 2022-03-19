<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests;

use Illuminate\Database\Eloquent\SoftDeletingScope;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\HasManyOfDescendants;
use Staudenmeir\LaravelAdjacencyList\Tests\Models\Post;
use Staudenmeir\LaravelAdjacencyList\Tests\Models\User;
use Staudenmeir\LaravelAdjacencyList\Tests\Scopes\DepthScope;

class HasManyOfDescendantsTest extends TestCase
{
    public function testLazyLoading()
    {
        $posts = User::find(2)->posts;

        $this->assertEquals([50, 80], $posts->pluck('id')->all());
    }

    public function testLazyLoadingAndSelf()
    {
        $posts = User::find(2)->postsAndSelf;

        $this->assertEquals([20, 50, 80], $posts->pluck('id')->all());
    }

    public function testLazyLoadingWithoutParentKey()
    {
        $posts = (new User())->posts()->get();

        $this->assertEmpty($posts);
    }

    public function testEagerLoading()
    {
        $users = User::with(['posts' => function (HasManyOfDescendants $query) {
            $query->orderBy('id');
        }])->get();

        $this->assertEquals([20, 30, 40, 50, 60, 70, 80], $users[0]->posts->pluck('id')->all());
        $this->assertEquals([50, 80], $users[1]->posts->pluck('id')->all());
        $this->assertEquals([], $users[8]->posts->pluck('id')->all());
        $this->assertEquals([100, 110], $users[9]->posts->pluck('id')->all());
        $this->assertArrayNotHasKey('laravel_paths', $users[0]->posts[0]);
    }

    public function testEagerLoadingAndSelf()
    {
        $users = User::with(['postsAndSelf' => function (HasManyOfDescendants $query) {
            $query->orderBy('id');
        }])->get();

        $this->assertEquals([10, 20, 30, 40, 50, 60, 70, 80], $users[0]->postsAndSelf->pluck('id')->all());
        $this->assertEquals([20, 50, 80], $users[1]->postsAndSelf->pluck('id')->all());
        $this->assertEquals([], $users[8]->postsAndSelf->pluck('id')->all());
        $this->assertEquals([100, 110], $users[9]->postsAndSelf->pluck('id')->all());
        $this->assertArrayNotHasKey('laravel_paths', $users[0]->postsAndSelf[0]);
    }

    public function testLazyEagerLoading()
    {
        $users = User::all()->load(['posts' => function (HasManyOfDescendants $query) {
            $query->orderBy('id');
        }]);

        $this->assertEquals([20, 30, 40, 50, 60, 70, 80], $users[0]->posts->pluck('id')->all());
        $this->assertEquals([50, 80], $users[1]->posts->pluck('id')->all());
        $this->assertEquals([], $users[8]->posts->pluck('id')->all());
        $this->assertEquals([100, 110], $users[9]->posts->pluck('id')->all());
        $this->assertArrayNotHasKey('laravel_paths', $users[0]->posts[0]);
    }

    public function testLazyEagerLoadingAndSelf()
    {
        $users = User::all()->load(['postsAndSelf' => function (HasManyOfDescendants $query) {
            $query->orderBy('id');
        }]);

        $this->assertEquals([10, 20, 30, 40, 50, 60, 70, 80], $users[0]->postsAndSelf->pluck('id')->all());
        $this->assertEquals([20, 50, 80], $users[1]->postsAndSelf->pluck('id')->all());
        $this->assertEquals([], $users[8]->postsAndSelf->pluck('id')->all());
        $this->assertEquals([100, 110], $users[9]->postsAndSelf->pluck('id')->all());
        $this->assertArrayNotHasKey('laravel_paths', $users[0]->postsAndSelf[0]);
    }

    public function testExistenceQuery()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $users = User::find(8)->ancestors()->has('posts', '>', 1)->get();

        $this->assertEquals([2, 1], $users->pluck('id')->all());
    }

    public function testExistenceQueryAndSelf()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $users = User::find(8)->ancestors()->has('postsAndSelf', '>', 2)->get();

        $this->assertEquals([2, 1], $users->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelation()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $users = User::has('posts', '>', 1)->get();

        $this->assertEquals([1, 2, 11], $users->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelationAndSelf()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $users = User::has('postsAndSelf', '>', 2)->get();

        $this->assertEquals([1, 2], $users->pluck('id')->all());
    }

    public function testUpdate()
    {
        if ($this->database === 'mariadb') {
            $this->markTestSkipped();
        }

        $affected = User::find(1)->posts()->update(['user_id' => 11]);

        $this->assertEquals(7, $affected);
        $this->assertEquals(11, Post::find(80)->user_id);
        $this->assertEquals(1, Post::find(10)->user_id);
    }

    public function testUpdateAndSelf()
    {
        if ($this->database === 'mariadb') {
            $this->markTestSkipped();
        }

        $affected = User::find(1)->postsAndSelf()->update(['user_id' => 11]);

        $this->assertEquals(8, $affected);
        $this->assertEquals(11, Post::find(80)->user_id);
        $this->assertEquals(11, Post::find(10)->user_id);
    }

    public function testWithTrashedDescendants()
    {
        $posts = User::find(4)->posts()->withTrashedDescendants()->get();

        $this->assertEquals([70, 90], $posts->pluck('id')->all());
    }

    public function testWithIntermediateScope()
    {
        $posts = User::find(2)->posts()->withIntermediateScope('depth', new DepthScope())->get();

        $this->assertEquals([50], $posts->pluck('id')->all());
    }

    public function testWithoutIntermediateScope()
    {
        $posts = User::find(2)->posts()
            ->withIntermediateScope('depth', new DepthScope())
            ->withoutIntermediateScope('depth')
            ->get();

        $this->assertEquals([50, 80], $posts->pluck('id')->all());
    }

    public function testWithoutIntermediateScopeWithObject()
    {
        $posts = User::find(4)->posts()->withoutIntermediateScope(new SoftDeletingScope())->get();

        $this->assertEquals([70, 90], $posts->pluck('id')->all());
    }

    public function testWithoutIntermediateScopes()
    {
        $posts = User::find(2)->posts()
            ->withIntermediateScope('depth', new DepthScope())
            ->withoutIntermediateScopes()
            ->get();

        $this->assertEquals([50, 80], $posts->pluck('id')->all());
    }

    public function testIntermediateScopes()
    {
        $relationship = User::find(2)->posts()->withIntermediateScope('depth', new DepthScope());

        $this->assertArrayHasKey('depth', $relationship->intermediateScopes());
    }

    public function testRemovedIntermediateScopes()
    {
        $relationship = User::find(2)->posts()
            ->withIntermediateScope('depth', new DepthScope())
            ->withoutIntermediateScope('depth');

        $this->assertSame(['depth'], $relationship->removedIntermediateScopes());
    }
}

<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Tree;

use Illuminate\Database\Eloquent\SoftDeletingScope;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\MorphToManyOfDescendants;
use Staudenmeir\LaravelAdjacencyList\Tests\Scopes\DepthScope;
use Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models\Tag;
use Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models\User;

class MorphToManyOfDescendantsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if ($this->connection === 'singlestore') {
            $this->markTestSkipped();
        }
    }

    public function testLazyLoading(): void
    {
        $tags = User::find(2)->tags;

        $this->assertEquals([52, 82], $tags->pluck('id')->all());
    }

    public function testLazyLoadingAndSelf(): void
    {
        $tags = User::find(2)->tagsAndSelf;

        $this->assertEquals([22, 52, 82], $tags->pluck('id')->all());
    }

    public function testEagerLoading(): void
    {
        $users = User::with([
            'tags' => fn (MorphToManyOfDescendants $query) => $query->orderBy('id'),
        ])->orderBy('id')->get();

        $this->assertEquals([22, 32, 42, 52, 62, 72, 82], $users[0]->tags->pluck('id')->all());
        $this->assertEquals([52, 82], $users[1]->tags->pluck('id')->all());
        $this->assertEquals([], $users[8]->tags->pluck('id')->all());
        $this->assertEquals([102, 112], $users[9]->tags->pluck('id')->all());
        $this->assertArrayNotHasKey('laravel_paths', $users[0]->tags[0]);
    }

    public function testEagerLoadingAndSelf(): void
    {
        $users = User::with([
            'tagsAndSelf' => fn (MorphToManyOfDescendants $query) => $query->orderBy('id'),
        ])->orderBy('id')->get();

        $this->assertEquals([12, 22, 32, 42, 52, 62, 72, 82], $users[0]->tagsAndSelf->pluck('id')->all());
        $this->assertEquals([22, 52, 82], $users[1]->tagsAndSelf->pluck('id')->all());
        $this->assertEquals([], $users[8]->tagsAndSelf->pluck('id')->all());
        $this->assertEquals([102, 112], $users[9]->tagsAndSelf->pluck('id')->all());
        $this->assertArrayNotHasKey('laravel_paths', $users[0]->tagsAndSelf[0]);
    }

    public function testLazyEagerLoading(): void
    {
        $users = User::orderBy('id')->get()->load([
            'tags' => fn (MorphToManyOfDescendants $query) => $query->orderBy('id'),
        ]);

        $this->assertEquals([22, 32, 42, 52, 62, 72, 82], $users[0]->tags->pluck('id')->all());
        $this->assertEquals([52, 82], $users[1]->tags->pluck('id')->all());
        $this->assertEquals([], $users[8]->tags->pluck('id')->all());
        $this->assertEquals([102, 112], $users[9]->tags->pluck('id')->all());
        $this->assertArrayNotHasKey('laravel_paths', $users[0]->tags[0]);
    }

    public function testLazyEagerLoadingAndSelf(): void
    {
        $users = User::orderBy('id')->get()->load([
            'tagsAndSelf' => fn (MorphToManyOfDescendants $query) => $query->orderBy('id'),
        ]);

        $this->assertEquals([12, 22, 32, 42, 52, 62, 72, 82], $users[0]->tagsAndSelf->pluck('id')->all());
        $this->assertEquals([22, 52, 82], $users[1]->tagsAndSelf->pluck('id')->all());
        $this->assertEquals([], $users[8]->tagsAndSelf->pluck('id')->all());
        $this->assertEquals([102, 112], $users[9]->tagsAndSelf->pluck('id')->all());
        $this->assertArrayNotHasKey('laravel_paths', $users[0]->tagsAndSelf[0]);
    }

    public function testExistenceQuery(): void
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $users = User::find(8)->ancestors()->has('tags', '>', 1)->get();

        $this->assertEquals([2, 1], $users->pluck('id')->all());
    }

    public function testExistenceQueryAndSelf(): void
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $users = User::find(8)->ancestors()->has('tagsAndSelf', '>', 2)->get();

        $this->assertEquals([2, 1], $users->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelation(): void
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $users = User::has('tags', '>', 1)->get();

        $this->assertEquals([1, 2, 11], $users->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelationAndSelf(): void
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $users = User::has('tagsAndSelf', '>', 2)->get();

        $this->assertEquals([1, 2], $users->pluck('id')->all());
    }

    public function testDelete(): void
    {
        if (in_array($this->connection, ['mariadb', 'firebird'])) {
            $this->markTestSkipped();
        }

        $affected = User::find(1)->tags()->delete();

        $this->assertEquals(7, $affected);
        $this->assertNotNull(Tag::withTrashed()->find(82)->deleted_at);
        $this->assertNull(Tag::find(12)->deleted_at);
    }

    public function testDeleteAndSelf(): void
    {
        if (in_array($this->connection, ['mariadb', 'firebird'])) {
            $this->markTestSkipped();
        }

        $affected = User::find(1)->tagsAndSelf()->delete();

        $this->assertEquals(8, $affected);
        $this->assertNotNull(Tag::withTrashed()->find(82)->deleted_at);
        $this->assertNotNull(Tag::withTrashed()->find(12)->deleted_at);
    }

    public function testWithTrashedDescendants(): void
    {
        $tags = User::find(4)->tags()->withTrashedDescendants()->get();

        $this->assertEquals([72, 92], $tags->pluck('id')->all());
    }

    public function testWithIntermediateScope(): void
    {
        $tags = User::find(2)->tags()->withIntermediateScope('depth', new DepthScope())->get();

        $this->assertEquals([52], $tags->pluck('id')->all());
    }

    public function testWithoutIntermediateScope(): void
    {
        $tags = User::find(2)->tags()
            ->withIntermediateScope('depth', new DepthScope())
            ->withoutIntermediateScope('depth')
            ->get();

        $this->assertEquals([52, 82], $tags->pluck('id')->all());
    }

    public function testWithoutIntermediateScopeWithObject(): void
    {
        $tags = User::find(4)->tags()->withoutIntermediateScope(new SoftDeletingScope())->get();

        $this->assertEquals([72, 92], $tags->pluck('id')->all());
    }

    public function testWithoutIntermediateScopes(): void
    {
        $tags = User::find(2)->tags()
            ->withIntermediateScope('depth', new DepthScope())
            ->withoutIntermediateScopes()
            ->get();

        $this->assertEquals([52, 82], $tags->pluck('id')->all());
    }

    public function testIntermediateScopes(): void
    {
        $relationship = User::find(2)->tags()->withIntermediateScope('depth', new DepthScope());

        $this->assertArrayHasKey('depth', $relationship->intermediateScopes());
    }

    public function testRemovedIntermediateScopes(): void
    {
        $relationship = User::find(2)->tags()
            ->withIntermediateScope('depth', new DepthScope())
            ->withoutIntermediateScope('depth');

        $this->assertSame(['depth'], $relationship->removedIntermediateScopes());
    }
}

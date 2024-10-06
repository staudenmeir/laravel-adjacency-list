<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Tree;

use Illuminate\Database\Eloquent\SoftDeletingScope;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\BelongsToManyOfDescendants;
use Staudenmeir\LaravelAdjacencyList\Tests\Scopes\DepthScope;
use Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models\Role;
use Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models\User;

class BelongsToManyOfDescendantsTest extends TestCase
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
        $roles = User::find(2)->roles;

        $this->assertEquals([51, 81], $roles->pluck('id')->all());
    }

    public function testLazyLoadingAndSelf(): void
    {
        $roles = User::find(2)->rolesAndSelf;

        $this->assertEquals([21, 51, 81], $roles->pluck('id')->all());
    }

    public function testEagerLoading(): void
    {
        $users = User::with([
            'roles' => fn (BelongsToManyOfDescendants $query) => $query->orderBy('id'),
        ])->orderBy('id')->get();

        $this->assertEquals([21, 31, 41, 51, 61, 71, 81], $users[0]->roles->pluck('id')->all());
        $this->assertEquals([51, 81], $users[1]->roles->pluck('id')->all());
        $this->assertEquals([], $users[8]->roles->pluck('id')->all());
        $this->assertEquals([101, 111], $users[9]->roles->pluck('id')->all());
        $this->assertArrayNotHasKey('laravel_paths', $users[0]->roles[0]);
    }

    public function testEagerLoadingAndSelf(): void
    {
        $users = User::with([
            'rolesAndSelf' => fn (BelongsToManyOfDescendants $query) => $query->orderBy('id'),
        ])->orderBy('id')->get();

        $this->assertEquals([11, 21, 31, 41, 51, 61, 71, 81], $users[0]->rolesAndSelf->pluck('id')->all());
        $this->assertEquals([21, 51, 81], $users[1]->rolesAndSelf->pluck('id')->all());
        $this->assertEquals([], $users[8]->rolesAndSelf->pluck('id')->all());
        $this->assertEquals([101, 111], $users[9]->rolesAndSelf->pluck('id')->all());
        $this->assertArrayNotHasKey('laravel_paths', $users[0]->rolesAndSelf[0]);
    }

    public function testLazyEagerLoading(): void
    {
        $users = User::orderBy('id')->get()->load([
            'roles' => fn (BelongsToManyOfDescendants $query) => $query->orderBy('id'),
        ]);

        $this->assertEquals([21, 31, 41, 51, 61, 71, 81], $users[0]->roles->pluck('id')->all());
        $this->assertEquals([51, 81], $users[1]->roles->pluck('id')->all());
        $this->assertEquals([], $users[8]->roles->pluck('id')->all());
        $this->assertEquals([101, 111], $users[9]->roles->pluck('id')->all());
        $this->assertArrayNotHasKey('laravel_paths', $users[0]->roles[0]);
    }

    public function testLazyEagerLoadingAndSelf(): void
    {
        $users = User::orderBy('id')->get()->load([
            'rolesAndSelf' => fn (BelongsToManyOfDescendants $query) => $query->orderBy('id'),
        ]);

        $this->assertEquals([11, 21, 31, 41, 51, 61, 71, 81], $users[0]->rolesAndSelf->pluck('id')->all());
        $this->assertEquals([21, 51, 81], $users[1]->rolesAndSelf->pluck('id')->all());
        $this->assertEquals([], $users[8]->rolesAndSelf->pluck('id')->all());
        $this->assertEquals([101, 111], $users[9]->rolesAndSelf->pluck('id')->all());
        $this->assertArrayNotHasKey('laravel_paths', $users[0]->rolesAndSelf[0]);
    }

    public function testExistenceQuery(): void
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $users = User::find(8)->ancestors()->has('roles', '>', 1)->get();

        $this->assertEquals([2, 1], $users->pluck('id')->all());
    }

    public function testExistenceQueryAndSelf(): void
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $users = User::find(8)->ancestors()->has('rolesAndSelf', '>', 2)->get();

        $this->assertEquals([2, 1], $users->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelation(): void
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $users = User::has('roles', '>', 1)->get();

        $this->assertEquals([1, 2, 11], $users->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelationAndSelf(): void
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        $users = User::has('rolesAndSelf', '>', 2)->get();

        $this->assertEquals([1, 2], $users->pluck('id')->all());
    }

    public function testDelete(): void
    {
        if (in_array($this->connection, ['mariadb', 'firebird'])) {
            $this->markTestSkipped();
        }

        $affected = User::find(1)->roles()->delete();

        $this->assertEquals(7, $affected);
        $this->assertNotNull(Role::withTrashed()->find(81)->deleted_at);
        $this->assertNull(Role::find(11)->deleted_at);
    }

    public function testDeleteAndSelf(): void
    {
        if (in_array($this->connection, ['mariadb', 'firebird'])) {
            $this->markTestSkipped();
        }

        $affected = User::find(1)->rolesAndSelf()->delete();

        $this->assertEquals(8, $affected);
        $this->assertNotNull(Role::withTrashed()->find(81)->deleted_at);
        $this->assertNotNull(Role::withTrashed()->find(11)->deleted_at);
    }

    public function testWithTrashedDescendants(): void
    {
        $roles = User::find(4)->roles()->withTrashedDescendants()->get();

        $this->assertEquals([71, 91], $roles->pluck('id')->all());
    }

    public function testWithIntermediateScope(): void
    {
        $roles = User::find(2)->roles()->withIntermediateScope('depth', new DepthScope())->get();

        $this->assertEquals([51], $roles->pluck('id')->all());
    }

    public function testWithoutIntermediateScope(): void
    {
        $roles = User::find(2)->roles()
            ->withIntermediateScope('depth', new DepthScope())
            ->withoutIntermediateScope('depth')
            ->get();

        $this->assertEquals([51, 81], $roles->pluck('id')->all());
    }

    public function testWithoutIntermediateScopeWithObject(): void
    {
        $roles = User::find(4)->roles()->withoutIntermediateScope(new SoftDeletingScope())->get();

        $this->assertEquals([71, 91], $roles->pluck('id')->all());
    }

    public function testWithoutIntermediateScopes(): void
    {
        $roles = User::find(2)->roles()
            ->withIntermediateScope('depth', new DepthScope())
            ->withoutIntermediateScopes()
            ->get();

        $this->assertEquals([51, 81], $roles->pluck('id')->all());
    }

    public function testIntermediateScopes(): void
    {
        $relationship = User::find(2)->roles()->withIntermediateScope('depth', new DepthScope());

        $this->assertArrayHasKey('depth', $relationship->intermediateScopes());
    }

    public function testRemovedIntermediateScopes(): void
    {
        $relationship = User::find(2)->roles()
            ->withIntermediateScope('depth', new DepthScope())
            ->withoutIntermediateScope('depth');

        $this->assertSame(['depth'], $relationship->removedIntermediateScopes());
    }
}

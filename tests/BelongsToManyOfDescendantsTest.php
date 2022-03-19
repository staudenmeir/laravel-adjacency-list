<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests;

use Illuminate\Database\Eloquent\SoftDeletingScope;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\BelongsToManyOfDescendants;
use Staudenmeir\LaravelAdjacencyList\Tests\Models\Role;
use Staudenmeir\LaravelAdjacencyList\Tests\Models\User;
use Staudenmeir\LaravelAdjacencyList\Tests\Scopes\DepthScope;

class BelongsToManyOfDescendantsTest extends TestCase
{
    public function testLazyLoading()
    {
        $roles = User::find(2)->roles;

        $this->assertEquals([51, 81], $roles->pluck('id')->all());
    }

    public function testLazyLoadingAndSelf()
    {
        $roles = User::find(2)->rolesAndSelf;

        $this->assertEquals([21, 51, 81], $roles->pluck('id')->all());
    }

    public function testEagerLoading()
    {
        $users = User::with(['roles' => function (BelongsToManyOfDescendants $query) {
            $query->orderBy('id');
        }])->get();

        $this->assertEquals([21, 31, 41, 51, 61, 71, 81], $users[0]->roles->pluck('id')->all());
        $this->assertEquals([51, 81], $users[1]->roles->pluck('id')->all());
        $this->assertEquals([], $users[8]->roles->pluck('id')->all());
        $this->assertEquals([101, 111], $users[9]->roles->pluck('id')->all());
        $this->assertArrayNotHasKey('laravel_paths', $users[0]->roles[0]);
    }

    public function testEagerLoadingAndSelf()
    {
        $users = User::with(['rolesAndSelf' => function (BelongsToManyOfDescendants $query) {
            $query->orderBy('id');
        }])->get();

        $this->assertEquals([11, 21, 31, 41, 51, 61, 71, 81], $users[0]->rolesAndSelf->pluck('id')->all());
        $this->assertEquals([21, 51, 81], $users[1]->rolesAndSelf->pluck('id')->all());
        $this->assertEquals([], $users[8]->rolesAndSelf->pluck('id')->all());
        $this->assertEquals([101, 111], $users[9]->rolesAndSelf->pluck('id')->all());
        $this->assertArrayNotHasKey('laravel_paths', $users[0]->rolesAndSelf[0]);
    }

    public function testLazyEagerLoading()
    {
        $users = User::all()->load(['roles' => function (BelongsToManyOfDescendants $query) {
            $query->orderBy('id');
        }]);

        $this->assertEquals([21, 31, 41, 51, 61, 71, 81], $users[0]->roles->pluck('id')->all());
        $this->assertEquals([51, 81], $users[1]->roles->pluck('id')->all());
        $this->assertEquals([], $users[8]->roles->pluck('id')->all());
        $this->assertEquals([101, 111], $users[9]->roles->pluck('id')->all());
        $this->assertArrayNotHasKey('laravel_paths', $users[0]->roles[0]);
    }

    public function testLazyEagerLoadingAndSelf()
    {
        $users = User::all()->load(['rolesAndSelf' => function (BelongsToManyOfDescendants $query) {
            $query->orderBy('id');
        }]);

        $this->assertEquals([11, 21, 31, 41, 51, 61, 71, 81], $users[0]->rolesAndSelf->pluck('id')->all());
        $this->assertEquals([21, 51, 81], $users[1]->rolesAndSelf->pluck('id')->all());
        $this->assertEquals([], $users[8]->rolesAndSelf->pluck('id')->all());
        $this->assertEquals([101, 111], $users[9]->rolesAndSelf->pluck('id')->all());
        $this->assertArrayNotHasKey('laravel_paths', $users[0]->rolesAndSelf[0]);
    }

    public function testExistenceQuery()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $users = User::find(8)->ancestors()->has('roles', '>', 1)->get();

        $this->assertEquals([2, 1], $users->pluck('id')->all());
    }

    public function testExistenceQueryAndSelf()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $users = User::find(8)->ancestors()->has('rolesAndSelf', '>', 2)->get();

        $this->assertEquals([2, 1], $users->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelation()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $users = User::has('roles', '>', 1)->get();

        $this->assertEquals([1, 2, 11], $users->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelationAndSelf()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $users = User::has('rolesAndSelf', '>', 2)->get();

        $this->assertEquals([1, 2], $users->pluck('id')->all());
    }

    public function testDelete()
    {
        if ($this->database === 'mariadb') {
            $this->markTestSkipped();
        }

        $affected = User::find(1)->roles()->delete();

        $this->assertEquals(7, $affected);
        $this->assertNotNull(Role::withTrashed()->find(81)->deleted_at);
        $this->assertNull(Role::find(11)->deleted_at);
    }

    public function testDeleteAndSelf()
    {
        if ($this->database === 'mariadb') {
            $this->markTestSkipped();
        }

        $affected = User::find(1)->rolesAndSelf()->delete();

        $this->assertEquals(8, $affected);
        $this->assertNotNull(Role::withTrashed()->find(81)->deleted_at);
        $this->assertNotNull(Role::withTrashed()->find(11)->deleted_at);
    }

    public function testWithTrashedDescendants()
    {
        $roles = User::find(4)->roles()->withTrashedDescendants()->get();

        $this->assertEquals([71, 91], $roles->pluck('id')->all());
    }

    public function testWithIntermediateScope()
    {
        $roles = User::find(2)->roles()->withIntermediateScope('depth', new DepthScope())->get();

        $this->assertEquals([51], $roles->pluck('id')->all());
    }

    public function testWithoutIntermediateScope()
    {
        $roles = User::find(2)->roles()
            ->withIntermediateScope('depth', new DepthScope())
            ->withoutIntermediateScope('depth')
            ->get();

        $this->assertEquals([51, 81], $roles->pluck('id')->all());
    }

    public function testWithoutIntermediateScopeWithObject()
    {
        $roles = User::find(4)->roles()->withoutIntermediateScope(new SoftDeletingScope())->get();

        $this->assertEquals([71, 91], $roles->pluck('id')->all());
    }

    public function testWithoutIntermediateScopes()
    {
        $roles = User::find(2)->roles()
            ->withIntermediateScope('depth', new DepthScope())
            ->withoutIntermediateScopes()
            ->get();

        $this->assertEquals([51, 81], $roles->pluck('id')->all());
    }

    public function testIntermediateScopes()
    {
        $relationship = User::find(2)->roles()->withIntermediateScope('depth', new DepthScope());

        $this->assertArrayHasKey('depth', $relationship->intermediateScopes());
    }

    public function testRemovedIntermediateScopes()
    {
        $relationship = User::find(2)->roles()
            ->withIntermediateScope('depth', new DepthScope())
            ->withoutIntermediateScope('depth');

        $this->assertSame(['depth'], $relationship->removedIntermediateScopes());
    }
}

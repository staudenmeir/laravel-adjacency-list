<?php

namespace Tests;

use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\TestCase as Base;
use Tests\Models\Post;
use Tests\Models\Role;
use Tests\Models\User;

abstract class TestCase extends Base
{
    protected function setUp(): void
    {
        parent::setUp();

        $config = require __DIR__ . '/config/database.php';

        $db = new DB();
        $db->addConnection($config[getenv('DATABASE') ?: 'sqlite']);
        $db->setAsGlobal();
        $db->bootEloquent();

        $this->migrate();

        $this->seed();
    }

    protected function tearDown(): void
    {
        DB::connection()->disconnect();

        parent::tearDown();
    }

    /**
     * Migrate the database.
     *
     * @return void
     */
    protected function migrate()
    {
        DB::schema()->dropAllTables();

        DB::schema()->create(
            'users',
            function (Blueprint $table) {
                $table->increments('id');
                $table->string('slug')->unique();
                $table->unsignedInteger('parent_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            }
        );

        DB::schema()->create(
            'posts',
            function (Blueprint $table) {
                $table->unsignedInteger('id');
                $table->unsignedInteger('user_id');
                $table->timestamps();
                $table->softDeletes();
            }
        );

        DB::schema()->create(
            'roles',
            function (Blueprint $table) {
                $table->unsignedInteger('id');
                $table->timestamps();
                $table->softDeletes();
            }
        );

        DB::schema()->create(
            'role_user',
            function (Blueprint $table) {
                $table->unsignedBigInteger('role_id');
                $table->unsignedBigInteger('user_id');
            }
        );
    }

    /**
     * Seed the database.
     *
     * @return void
     */
    protected function seed()
    {
        Model::unguard();

        User::create(['slug' => 'user-1', 'parent_id' => null, 'deleted_at' => null]);
        User::create(['slug' => 'user-2', 'parent_id' => 1, 'deleted_at' => null]);
        User::create(['slug' => 'user-3', 'parent_id' => 1, 'deleted_at' => null]);
        User::create(['slug' => 'user-4', 'parent_id' => 1, 'deleted_at' => null]);
        User::create(['slug' => 'user-5', 'parent_id' => 2, 'deleted_at' => null]);
        User::create(['slug' => 'user-6', 'parent_id' => 3, 'deleted_at' => null]);
        User::create(['slug' => 'user-7', 'parent_id' => 4, 'deleted_at' => null]);
        User::create(['slug' => 'user-8', 'parent_id' => 5, 'deleted_at' => null]);
        User::create(['slug' => 'user-9', 'parent_id' => 6, 'deleted_at' => null]);
        User::create(['slug' => 'user-10', 'parent_id' => 7, 'deleted_at' => Carbon::now()]);
        User::create(['slug' => 'user-11', 'parent_id' => null, 'deleted_at' => null]);
        User::create(['slug' => 'user-12', 'parent_id' => 11, 'deleted_at' => null]);

        Post::create(['id' => 10, 'user_id' => 1, 'deleted_at' => null]);
        Post::create(['id' => 20, 'user_id' => 2, 'deleted_at' => null]);
        Post::create(['id' => 30, 'user_id' => 3, 'deleted_at' => null]);
        Post::create(['id' => 40, 'user_id' => 4, 'deleted_at' => null]);
        Post::create(['id' => 50, 'user_id' => 5, 'deleted_at' => null]);
        Post::create(['id' => 60, 'user_id' => 6, 'deleted_at' => null]);
        Post::create(['id' => 70, 'user_id' => 7, 'deleted_at' => null]);
        Post::create(['id' => 80, 'user_id' => 8, 'deleted_at' => null]);
        Post::create(['id' => 90, 'user_id' => 10, 'deleted_at' => null]);
        Post::create(['id' => 100, 'user_id' => 12, 'deleted_at' => null]);
        Post::create(['id' => 110, 'user_id' => 12, 'deleted_at' => null]);
        Post::create(['id' => 120, 'user_id' => 12, 'deleted_at' => Carbon::now()]);

        Role::create(['id' => 11, 'deleted_at' => null]);
        Role::create(['id' => 21, 'deleted_at' => null]);
        Role::create(['id' => 31, 'deleted_at' => null]);
        Role::create(['id' => 41, 'deleted_at' => null]);
        Role::create(['id' => 51, 'deleted_at' => null]);
        Role::create(['id' => 61, 'deleted_at' => null]);
        Role::create(['id' => 71, 'deleted_at' => null]);
        Role::create(['id' => 81, 'deleted_at' => null]);
        Role::create(['id' => 91, 'deleted_at' => null]);
        Role::create(['id' => 101, 'deleted_at' => null]);
        Role::create(['id' => 111, 'deleted_at' => null]);
        Role::create(['id' => 121, 'deleted_at' => Carbon::now()]);

        DB::table('role_user')->insert(
            [
                ['role_id' => 11, 'user_id' => 1],
                ['role_id' => 21, 'user_id' => 2],
                ['role_id' => 31, 'user_id' => 3],
                ['role_id' => 41, 'user_id' => 4],
                ['role_id' => 51, 'user_id' => 5],
                ['role_id' => 61, 'user_id' => 6],
                ['role_id' => 71, 'user_id' => 7],
                ['role_id' => 81, 'user_id' => 8],
                ['role_id' => 91, 'user_id' => 10],
                ['role_id' => 101, 'user_id' => 12],
                ['role_id' => 111, 'user_id' => 12],
                ['role_id' => 121, 'user_id' => 12],
            ]
        );

        Model::reguard();
    }
}

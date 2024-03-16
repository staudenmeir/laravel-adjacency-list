<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Tree;

use Carbon\Carbon;
use HarryGulliford\Firebird\FirebirdServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Base;
use SingleStore\Laravel\SingleStoreProvider;
use Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models\Category;
use Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models\Post;
use Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models\Role;
use Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models\Tag;
use Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models\User;
use Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models\Video;

abstract class TestCase extends Base
{
    protected string $connection;

    protected function setUp(): void
    {
        $this->connection = getenv('DB_CONNECTION') ?: 'sqlite';

        parent::setUp();

        $this->migrateDatabase();

        $this->seedDatabase();
    }

    protected function tearDown(): void
    {
        DB::connection()->disconnect();

        parent::tearDown();
    }

    protected function migrateDatabase(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('videos');
        Schema::dropIfExists('authorables');
        Schema::dropIfExists('categories');

        Schema::create(
            'users',
            function (Blueprint $table) {
                $table->unsignedBigInteger('id')->unique();
                $table->string('slug');
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->unsignedBigInteger('followers');
                $table->timestamps();
                $table->softDeletes();

                if ($this->connection === 'singlestore') {
                    $table->shardKey('id');
                }
            }
        );

        Schema::create(
            'posts',
            function (Blueprint $table) {
                $table->unsignedBigInteger('id')->unique();
                $table->unsignedBigInteger('user_id');
                $table->timestamps();
                $table->softDeletes();

                if ($this->connection === 'singlestore') {
                    $table->shardKey('id');
                }
            }
        );

        Schema::create(
            'roles',
            function (Blueprint $table) {
                $table->unsignedBigInteger('id')->unique();
                $table->timestamps();
                $table->softDeletes();

                if ($this->connection === 'singlestore') {
                    $table->shardKey('id');
                }
            }
        );

        Schema::create(
            'role_user',
            function (Blueprint $table) {
                $table->unsignedBigInteger('role_id');
                $table->unsignedBigInteger('user_id');
            }
        );

        Schema::create(
            'tags',
            function (Blueprint $table) {
                $table->unsignedBigInteger('id')->unique();
                $table->timestamps();
                $table->softDeletes();

                if ($this->connection === 'singlestore') {
                    $table->shardKey('id');
                }
            }
        );

        Schema::create(
            'taggables',
            function (Blueprint $table) {
                $table->unsignedBigInteger('tag_id');
                $table->morphs('taggable');
            }
        );

        Schema::create(
            'videos',
            function (Blueprint $table) {
                $table->unsignedBigInteger('id')->unique();
                $table->timestamps();
                $table->softDeletes();

                if ($this->connection === 'singlestore') {
                    $table->shardKey('id');
                }
            }
        );

        Schema::create(
            'authorables',
            function (Blueprint $table) {
                $table->unsignedBigInteger('user_id');
                $table->morphs('authorable');
            }
        );

        Schema::create(
            'categories',
            function (Blueprint $table) {
                $table->string('id')->unique();
                $table->string('parent_id')->nullable();
                $table->timestamps();

                if ($this->connection === 'singlestore') {
                    $table->shardKey('id');
                }
            }
        );
    }

    protected function seedDatabase(): void
    {
        Model::unguard();

        User::create(['id' => 1, 'slug' => 'user-1', 'parent_id' => null, 'followers' => 1, 'deleted_at' => null]);
        User::create(['id' => 2, 'slug' => 'user-2', 'parent_id' => 1, 'followers' => 1, 'deleted_at' => null]);
        User::create(['id' => 3, 'slug' => 'user-3', 'parent_id' => 1, 'followers' => 1, 'deleted_at' => null]);
        User::create(['id' => 4, 'slug' => 'user-4', 'parent_id' => 1, 'followers' => 1, 'deleted_at' => null]);
        User::create(['id' => 5, 'slug' => 'user-5', 'parent_id' => 2, 'followers' => 1, 'deleted_at' => null]);
        User::create(['id' => 6, 'slug' => 'user-6', 'parent_id' => 3, 'followers' => 1, 'deleted_at' => null]);
        User::create(['id' => 7, 'slug' => 'user-7', 'parent_id' => 4, 'followers' => 1, 'deleted_at' => null]);
        User::create(['id' => 8, 'slug' => 'user-8', 'parent_id' => 5, 'followers' => 1, 'deleted_at' => null]);
        User::create(['id' => 9, 'slug' => 'user-9', 'parent_id' => 6, 'followers' => 1, 'deleted_at' => null]);
        User::create(['id' => 10, 'slug' => 'user-10', 'parent_id' => 7, 'followers' => 1, 'deleted_at' => Carbon::now()]);
        User::create(['id' => 11, 'slug' => 'user-11', 'parent_id' => null, 'followers' => 1, 'deleted_at' => null]);
        User::create(['id' => 12, 'slug' => 'user-12', 'parent_id' => 11, 'followers' => 1, 'deleted_at' => null]);

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

        DB::table('role_user')->insert(['role_id' => 11, 'user_id' => 1]);
        DB::table('role_user')->insert(['role_id' => 21, 'user_id' => 2]);
        DB::table('role_user')->insert(['role_id' => 31, 'user_id' => 3]);
        DB::table('role_user')->insert(['role_id' => 41, 'user_id' => 4]);
        DB::table('role_user')->insert(['role_id' => 51, 'user_id' => 5]);
        DB::table('role_user')->insert(['role_id' => 61, 'user_id' => 6]);
        DB::table('role_user')->insert(['role_id' => 71, 'user_id' => 7]);
        DB::table('role_user')->insert(['role_id' => 81, 'user_id' => 8]);
        DB::table('role_user')->insert(['role_id' => 91, 'user_id' => 10]);
        DB::table('role_user')->insert(['role_id' => 101, 'user_id' => 12]);
        DB::table('role_user')->insert(['role_id' => 111, 'user_id' => 12]);
        DB::table('role_user')->insert(['role_id' => 121, 'user_id' => 12]);

        Tag::create(['id' => 12, 'deleted_at' => null]);
        Tag::create(['id' => 22, 'deleted_at' => null]);
        Tag::create(['id' => 32, 'deleted_at' => null]);
        Tag::create(['id' => 42, 'deleted_at' => null]);
        Tag::create(['id' => 52, 'deleted_at' => null]);
        Tag::create(['id' => 62, 'deleted_at' => null]);
        Tag::create(['id' => 72, 'deleted_at' => null]);
        Tag::create(['id' => 82, 'deleted_at' => null]);
        Tag::create(['id' => 92, 'deleted_at' => null]);
        Tag::create(['id' => 102, 'deleted_at' => null]);
        Tag::create(['id' => 112, 'deleted_at' => null]);
        Tag::create(['id' => 122, 'deleted_at' => Carbon::now()]);

        DB::table('taggables')->insert(['tag_id' => 12, 'taggable_type' => User::class, 'taggable_id' => 1]);
        DB::table('taggables')->insert(['tag_id' => 22, 'taggable_type' => User::class, 'taggable_id' => 2]);
        DB::table('taggables')->insert(['tag_id' => 32, 'taggable_type' => User::class, 'taggable_id' => 3]);
        DB::table('taggables')->insert(['tag_id' => 42, 'taggable_type' => User::class, 'taggable_id' => 4]);
        DB::table('taggables')->insert(['tag_id' => 52, 'taggable_type' => User::class, 'taggable_id' => 5]);
        DB::table('taggables')->insert(['tag_id' => 62, 'taggable_type' => User::class, 'taggable_id' => 6]);
        DB::table('taggables')->insert(['tag_id' => 72, 'taggable_type' => User::class, 'taggable_id' => 7]);
        DB::table('taggables')->insert(['tag_id' => 82, 'taggable_type' => User::class, 'taggable_id' => 8]);
        DB::table('taggables')->insert(['tag_id' => 92, 'taggable_type' => User::class, 'taggable_id' => 10]);
        DB::table('taggables')->insert(['tag_id' => 102, 'taggable_type' => User::class, 'taggable_id' => 12]);
        DB::table('taggables')->insert(['tag_id' => 112, 'taggable_type' => User::class, 'taggable_id' => 12]);
        DB::table('taggables')->insert(['tag_id' => 122, 'taggable_type' => User::class, 'taggable_id' => 12]);
        DB::table('taggables')->insert(['tag_id' => 12, 'taggable_type' => Post::class, 'taggable_id' => 5]);

        Video::create(['id' => 13, 'deleted_at' => null]);
        Video::create(['id' => 23, 'deleted_at' => null]);
        Video::create(['id' => 33, 'deleted_at' => null]);
        Video::create(['id' => 43, 'deleted_at' => null]);
        Video::create(['id' => 53, 'deleted_at' => null]);
        Video::create(['id' => 63, 'deleted_at' => null]);
        Video::create(['id' => 73, 'deleted_at' => null]);
        Video::create(['id' => 83, 'deleted_at' => null]);
        Video::create(['id' => 93, 'deleted_at' => null]);
        Video::create(['id' => 103, 'deleted_at' => null]);
        Video::create(['id' => 113, 'deleted_at' => null]);
        Video::create(['id' => 123, 'deleted_at' => Carbon::now()]);

        DB::table('authorables')->insert(['user_id' => 1, 'authorable_type' => Video::class, 'authorable_id' => 13]);
        DB::table('authorables')->insert(['user_id' => 2, 'authorable_type' => Video::class, 'authorable_id' => 23]);
        DB::table('authorables')->insert(['user_id' => 3, 'authorable_type' => Video::class, 'authorable_id' => 33]);
        DB::table('authorables')->insert(['user_id' => 4, 'authorable_type' => Video::class, 'authorable_id' => 43]);
        DB::table('authorables')->insert(['user_id' => 5, 'authorable_type' => Video::class, 'authorable_id' => 53]);
        DB::table('authorables')->insert(['user_id' => 6, 'authorable_type' => Video::class, 'authorable_id' => 63]);
        DB::table('authorables')->insert(['user_id' => 7, 'authorable_type' => Video::class, 'authorable_id' => 73]);
        DB::table('authorables')->insert(['user_id' => 8, 'authorable_type' => Video::class, 'authorable_id' => 83]);
        DB::table('authorables')->insert(['user_id' => 10, 'authorable_type' => Video::class, 'authorable_id' => 93]);
        DB::table('authorables')->insert(['user_id' => 12, 'authorable_type' => Video::class, 'authorable_id' => 103]);
        DB::table('authorables')->insert(['user_id' => 12, 'authorable_type' => Video::class, 'authorable_id' => 113]);
        DB::table('authorables')->insert(['user_id' => 12, 'authorable_type' => Video::class, 'authorable_id' => 123]);
        DB::table('authorables')->insert(['user_id' => 5, 'authorable_type' => Post::class, 'authorable_id' => 13]);

        Category::create(['id' => 'a', 'parent_id' => null]);
        Category::create(['id' => 'd', 'parent_id' => 'a']);
        Category::create(['id' => 'c', 'parent_id' => 'a']);
        Category::create(['id' => 'b', 'parent_id' => 'a']);

        Model::reguard();
    }

    protected function getEnvironmentSetUp($app)
    {
        $config = require __DIR__.'/../config/database.php';

        $app['config']->set('database.default', 'testing');

        $app['config']->set('database.connections.testing', $config[$this->connection]);
    }

    protected function getPackageProviders($app)
    {
        return [SingleStoreProvider::class]; // TODO[L11]
        return [SingleStoreProvider::class, FirebirdServiceProvider::class];
    }
}

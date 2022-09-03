<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Tree;

use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\TestCase as Base;
use Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models\Category;
use Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models\Post;
use Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models\Role;
use Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models\Tag;
use Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models\User;
use Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models\Video;

abstract class TestCase extends Base
{
    protected string $database;

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = getenv('DATABASE') ?: 'sqlite';

        $config = require __DIR__.'/../config/database.php';

        $db = new DB();
        $db->addConnection($config[$this->database]);
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

    protected function migrate(): void
    {
        DB::schema()->dropAllTables();

        DB::schema()->create(
            'users',
            function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->unsignedInteger('parent_id')->nullable();
                $table->unsignedBigInteger('followers')->default(1);
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

        DB::schema()->create(
            'tags',
            function (Blueprint $table) {
                $table->unsignedInteger('id');
                $table->timestamps();
                $table->softDeletes();
            }
        );

        DB::schema()->create(
            'taggables',
            function (Blueprint $table) {
                $table->unsignedInteger('tag_id');
                $table->morphs('taggable');
            }
        );

        DB::schema()->create(
            'videos',
            function (Blueprint $table) {
                $table->unsignedInteger('id');
                $table->timestamps();
                $table->softDeletes();
            }
        );

        DB::schema()->create(
            'authorables',
            function (Blueprint $table) {
                $table->unsignedInteger('user_id');
                $table->morphs('authorable');
            }
        );

        DB::schema()->create(
            'categories',
            function (Blueprint $table) {
                $table->string('id');
                $table->string('parent_id')->nullable();
                $table->timestamps();
            }
        );
    }

    protected function seed(): void
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

        DB::table('taggables')->insert(
            [
                ['tag_id' => 12, 'taggable_type' => User::class, 'taggable_id' => 1],
                ['tag_id' => 22, 'taggable_type' => User::class, 'taggable_id' => 2],
                ['tag_id' => 32, 'taggable_type' => User::class, 'taggable_id' => 3],
                ['tag_id' => 42, 'taggable_type' => User::class, 'taggable_id' => 4],
                ['tag_id' => 52, 'taggable_type' => User::class, 'taggable_id' => 5],
                ['tag_id' => 62, 'taggable_type' => User::class, 'taggable_id' => 6],
                ['tag_id' => 72, 'taggable_type' => User::class, 'taggable_id' => 7],
                ['tag_id' => 82, 'taggable_type' => User::class, 'taggable_id' => 8],
                ['tag_id' => 92, 'taggable_type' => User::class, 'taggable_id' => 10],
                ['tag_id' => 102, 'taggable_type' => User::class, 'taggable_id' => 12],
                ['tag_id' => 112, 'taggable_type' => User::class, 'taggable_id' => 12],
                ['tag_id' => 122, 'taggable_type' => User::class, 'taggable_id' => 12],
                ['tag_id' => 12, 'taggable_type' => Post::class, 'taggable_id' => 5],
            ]
        );

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

        DB::table('authorables')->insert(
            [
                ['user_id' => 1, 'authorable_type' => Video::class, 'authorable_id' => 13],
                ['user_id' => 2, 'authorable_type' => Video::class, 'authorable_id' => 23],
                ['user_id' => 3, 'authorable_type' => Video::class, 'authorable_id' => 33],
                ['user_id' => 4, 'authorable_type' => Video::class, 'authorable_id' => 43],
                ['user_id' => 5, 'authorable_type' => Video::class, 'authorable_id' => 53],
                ['user_id' => 6, 'authorable_type' => Video::class, 'authorable_id' => 63],
                ['user_id' => 7, 'authorable_type' => Video::class, 'authorable_id' => 73],
                ['user_id' => 8, 'authorable_type' => Video::class, 'authorable_id' => 83],
                ['user_id' => 10, 'authorable_type' => Video::class, 'authorable_id' => 93],
                ['user_id' => 12, 'authorable_type' => Video::class, 'authorable_id' => 103],
                ['user_id' => 12, 'authorable_type' => Video::class, 'authorable_id' => 113],
                ['user_id' => 12, 'authorable_type' => Video::class, 'authorable_id' => 123],
                ['user_id' => 5, 'authorable_type' => Post::class, 'authorable_id' => 13],
            ]
        );

        Category::create(['id' => 'a', 'parent_id' => null]);
        Category::create(['id' => 'd', 'parent_id' => 'a']);
        Category::create(['id' => 'c', 'parent_id' => 'a']);
        Category::create(['id' => 'b', 'parent_id' => 'a']);

        Model::reguard();
    }
}

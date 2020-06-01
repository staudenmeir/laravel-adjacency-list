<?php

namespace Tests;

use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\TestCase as Base;
use Tests\Models\User;

abstract class TestCase extends Base
{
    protected function setUp(): void
    {
        parent::setUp();

        $config = require __DIR__.'/config/database.php';

        $db = new DB;
        $db->addConnection($config[getenv('DATABASE') ?: 'sqlite']);
        $db->setAsGlobal();
        $db->bootEloquent();

        $this->migrate();

        $this->seed();
    }

    /**
     * Migrate the database.
     *
     * @return void
     */
    protected function migrate()
    {
        DB::schema()->dropAllTables();

        DB::schema()->create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug')->unique();
            $table->unsignedInteger('parent_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
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

        Model::reguard();
    }
}

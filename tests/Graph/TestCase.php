<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Graph;

use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\TestCase as Base;
use Staudenmeir\LaravelAdjacencyList\Tests\Graph\Models\Node;

abstract class TestCase extends Base
{
    protected string $database;

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = getenv('DATABASE') ?: 'sqlite';

        if ($this->database === 'sqlsrv') {
            $this->markTestSkipped();
        }

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
            'nodes',
            function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->timestamps();
                $table->softDeletes();
            }
        );

        DB::schema()->create(
            'edges',
            function (Blueprint $table) {
                $table->unsignedBigInteger('parent_id');
                $table->unsignedBigInteger('child_id');
                $table->string('label');
                $table->tinyInteger('weight');
                $table->timestamp('created_at');
            }
        );
    }

    protected function seed(): void
    {
        Carbon::setTestNow(
            Carbon::now()
        );

        Model::unguard();

        Node::create(['id' => 14, 'slug' => 'node-14']);
        Node::create(['id' => 24, 'slug' => 'node-24']);
        Node::create(['id' => 34, 'slug' => 'node-34']);
        Node::create(['id' => 44, 'slug' => 'node-44']);
        Node::create(['id' => 54, 'slug' => 'node-54']);
        Node::create(['id' => 64, 'slug' => 'node-64']);
        Node::create(['id' => 74, 'slug' => 'node-74']);
        Node::create(['id' => 84, 'slug' => 'node-84']);
        Node::create(['id' => 94, 'slug' => 'node-94']);
        Node::create(['id' => 104, 'slug' => 'node-104']);
        Node::create(['id' => 114, 'slug' => 'node-114', 'deleted_at' => Carbon::now()]);

        DB::table('edges')->insert(
            [
                [
                    'parent_id' => 14,
                    'child_id' => 24,
                    'label' => 'a',
                    'weight' => 1,
                    'created_at' => Carbon::now(),
                ],
                [
                    'parent_id' => 14,
                    'child_id' => 34,
                    'label' => 'b',
                    'weight' => 2,
                    'created_at' => Carbon::now(),
                ],
                [
                    'parent_id' => 14,
                    'child_id' => 44,
                    'label' => 'c',
                    'weight' => 3,
                    'created_at' => Carbon::now(),
                ],
                [
                    'parent_id' => 14,
                    'child_id' => 54,
                    'label' => 'd',
                    'weight' => 4,
                    'created_at' => Carbon::now(),
                ],
                [
                    'parent_id' => 24,
                    'child_id' => 54,
                    'label' => 'e',
                    'weight' => 5,
                    'created_at' => Carbon::now(),
                ],
                [
                    'parent_id' => 34,
                    'child_id' => 64,
                    'label' => 'f',
                    'weight' => 6,
                    'created_at' => Carbon::now(),
                ],
                [
                    'parent_id' => 54,
                    'child_id' => 74,
                    'label' => 'g',
                    'weight' => 7,
                    'created_at' => Carbon::now(),
                ],
                [
                    'parent_id' => 54,
                    'child_id' => 84,
                    'label' => 'h',
                    'weight' => 8,
                    'created_at' => Carbon::now(),
                ],
                [
                    'parent_id' => 74,
                    'child_id' => 84,
                    'label' => 'i',
                    'weight' => 9,
                    'created_at' => Carbon::now(),
                ],
                [
                    'parent_id' => 94,
                    'child_id' => 24,
                    'label' => 'j',
                    'weight' => 10,
                    'created_at' => Carbon::now(),
                ],
                [
                    'parent_id' => 104,
                    'child_id' => 54,
                    'label' => 'k',
                    'weight' => 11,
                    'created_at' => Carbon::now(),
                ],
                [
                    'parent_id' => 114,
                    'child_id' => 54,
                    'label' => 'l',
                    'weight' => 12,
                    'created_at' => Carbon::now(),
                ],
            ]
        );

        Model::reguard();
    }

    protected function seedCycle(): void
    {
        Model::unguard();

        Node::create(['id' => 124, 'slug' => 'node-124']);
        Node::create(['id' => 134, 'slug' => 'node-134']);
        Node::create(['id' => 144, 'slug' => 'node-144']);

        DB::table('edges')->insert(
            [

                [
                    'parent_id' => 124,
                    'child_id' => 134,
                    'label' => 'm',
                    'weight' => 13,
                    'created_at' => Carbon::now(),
                ],
                [
                    'parent_id' => 134,
                    'child_id' => 144,
                    'label' => 'n',
                    'weight' => 14,
                    'created_at' => Carbon::now(),
                ],
                [
                    'parent_id' => 144,
                    'child_id' => 124,
                    'label' => 'o',
                    'weight' => 15,
                    'created_at' => Carbon::now(),
                ],
            ]
        );

        Model::reguard();
    }
}

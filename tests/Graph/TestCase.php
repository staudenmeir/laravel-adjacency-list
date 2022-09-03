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

        Node::create(['id' => 1, 'slug' => 'node-1']);
        Node::create(['id' => 2, 'slug' => 'node-2']);
        Node::create(['id' => 3, 'slug' => 'node-3']);
        Node::create(['id' => 4, 'slug' => 'node-4']);
        Node::create(['id' => 5, 'slug' => 'node-5']);
        Node::create(['id' => 6, 'slug' => 'node-6']);
        Node::create(['id' => 7, 'slug' => 'node-7']);
        Node::create(['id' => 8, 'slug' => 'node-8']);
        Node::create(['id' => 9, 'slug' => 'node-9']);
        Node::create(['id' => 10, 'slug' => 'node-10']);
        Node::create(['id' => 11, 'slug' => 'node-11', 'deleted_at' => Carbon::now()]);

        DB::table('edges')->insert(
            [
                [
                    'parent_id' => 1,
                    'child_id' => 2,
                    'label' => 'a',
                    'weight' => 1,
                    'created_at' => Carbon::now(),
                ],
                [
                    'parent_id' => 1,
                    'child_id' => 3,
                    'label' => 'b',
                    'weight' => 2,
                    'created_at' => Carbon::now(),
                ],
                [
                    'parent_id' => 1,
                    'child_id' => 4,
                    'label' => 'c',
                    'weight' => 3,
                    'created_at' => Carbon::now(),
                ],
                [
                    'parent_id' => 1,
                    'child_id' => 5,
                    'label' => 'd',
                    'weight' => 4,
                    'created_at' => Carbon::now(),
                ],
                [
                    'parent_id' => 2,
                    'child_id' => 5,
                    'label' => 'e',
                    'weight' => 5,
                    'created_at' => Carbon::now(),
                ],
                [
                    'parent_id' => 3,
                    'child_id' => 6,
                    'label' => 'f',
                    'weight' => 6,
                    'created_at' => Carbon::now(),
                ],
                [
                    'parent_id' => 5,
                    'child_id' => 7,
                    'label' => 'g',
                    'weight' => 7,
                    'created_at' => Carbon::now(),
                ],
                [
                    'parent_id' => 5,
                    'child_id' => 8,
                    'label' => 'h',
                    'weight' => 8,
                    'created_at' => Carbon::now(),
                ],
                [
                    'parent_id' => 7,
                    'child_id' => 8,
                    'label' => 'i',
                    'weight' => 9,
                    'created_at' => Carbon::now(),
                ],
                [
                    'parent_id' => 9,
                    'child_id' => 2,
                    'label' => 'j',
                    'weight' => 10,
                    'created_at' => Carbon::now(),
                ],
                [
                    'parent_id' => 10,
                    'child_id' => 5,
                    'label' => 'k',
                    'weight' => 11,
                    'created_at' => Carbon::now(),
                ],
                [
                    'parent_id' => 11,
                    'child_id' => 5,
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

        Node::create(['id' => 12, 'slug' => 'node-12']);
        Node::create(['id' => 13, 'slug' => 'node-13']);
        Node::create(['id' => 14, 'slug' => 'node-14']);

        DB::table('edges')->insert(
            [

                [
                    'parent_id' => 12,
                    'child_id' => 13,
                    'label' => 'm',
                    'weight' => 13,
                    'created_at' => Carbon::now(),
                ],
                [
                    'parent_id' => 13,
                    'child_id' => 14,
                    'label' => 'n',
                    'weight' => 14,
                    'created_at' => Carbon::now(),
                ],
                [
                    'parent_id' => 14,
                    'child_id' => 12,
                    'label' => 'o',
                    'weight' => 15,
                    'created_at' => Carbon::now(),
                ],
            ]
        );

        Model::reguard();
    }
}

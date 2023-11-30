<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Graph;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Base;
use Staudenmeir\LaravelAdjacencyList\Tests\Graph\Models\Node;
use Staudenmeir\LaravelAdjacencyList\Tests\Graph\Models\NodeWithCycleDetection;
use Staudenmeir\LaravelAdjacencyList\Tests\Graph\Models\NodeWithCycleDetectionAndStart;
use Staudenmeir\LaravelAdjacencyList\Tests\Graph\Models\NodeWithUuidAndCycleDetection;
use Staudenmeir\LaravelAdjacencyList\Tests\Graph\Models\NodeWithUuidAndCycleDetectionAndStart;

abstract class TestCase extends Base
{
    protected string $database;

    protected function setUp(): void
    {
        $this->database = getenv('DATABASE') ?: 'sqlite';

        parent::setUp();

        if ($this->database === 'singlestore') {
            $this->markTestSkipped();
        }

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
        Schema::dropAllTables();

        Schema::create(
            'nodes',
            function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->uuid()->unique();
                $table->timestamps();
                $table->softDeletes();
            }
        );

        Schema::create(
            'edges',
            function (Blueprint $table) {
                $table->unsignedBigInteger('parent_id');
                $table->unsignedBigInteger('child_id');
                $table->uuid('parent_uuid');
                $table->uuid('child_uuid');
                $table->string('label');
                $table->tinyInteger('weight');
                $table->decimal('value', 8, 3);
                $table->timestamp('created_at');
            }
        );
    }

    protected function seedDatabase(): void
    {
        Carbon::setTestNow(
            Carbon::now()->roundSecond()
        );

        Model::unguard();

        Node::create(['slug' => 'node-1', 'uuid' => 'a0f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b']);
        Node::create(['slug' => 'node-2', 'uuid' => 'b0f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b']);
        Node::create(['slug' => 'node-3', 'uuid' => 'c0f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b']);
        Node::create(['slug' => 'node-4', 'uuid' => 'd0f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b']);
        Node::create(['slug' => 'node-5', 'uuid' => 'e0f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b']);
        Node::create(['slug' => 'node-6', 'uuid' => 'f0f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b']);
        Node::create(['slug' => 'node-7', 'uuid' => 'a1f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b']);
        Node::create(['slug' => 'node-8', 'uuid' => 'b1f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b']);
        Node::create(['slug' => 'node-9', 'uuid' => 'c1f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b']);
        Node::create(['slug' => 'node-10', 'uuid' => 'd1f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b']);
        Node::create(['slug' => 'node-11', 'uuid' => 'e1f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b', 'deleted_at' => Carbon::now()]);

        DB::table('edges')->insert(
            [
                [
                    'parent_id' => 1,
                    'child_id' => 2,
                    'parent_uuid' => 'a0f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b',
                    'child_uuid' => 'b0f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b',
                    'label' => 'a',
                    'weight' => 1,
                    'value' => '123.456',
                    'created_at' => Carbon::now(),
                ],
                [
                    'parent_id' => 1,
                    'child_id' => 3,
                    'parent_uuid' => 'a0f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b',
                    'child_uuid' => 'c0f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b',
                    'label' => 'b',
                    'weight' => 2,
                    'value' => '123.456',
                    'created_at' => Carbon::now(),
                ],
                [
                    'parent_id' => 1,
                    'child_id' => 4,
                    'parent_uuid' => 'a0f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b',
                    'child_uuid' => 'd0f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b',
                    'label' => 'c',
                    'weight' => 3,
                    'value' => '123.456',
                    'created_at' => Carbon::now(),
                ],
                [
                    'parent_id' => 1,
                    'child_id' => 5,
                    'parent_uuid' => 'a0f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b',
                    'child_uuid' => 'e0f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b',
                    'label' => 'd',
                    'weight' => 4,
                    'value' => '123.456',
                    'created_at' => Carbon::now(),
                ],
                [
                    'parent_id' => 2,
                    'child_id' => 5,
                    'parent_uuid' => 'b0f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b',
                    'child_uuid' => 'e0f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b',
                    'label' => 'e',
                    'weight' => 5,
                    'value' => '123.456',
                    'created_at' => Carbon::now(),
                ],
                [
                    'parent_id' => 3,
                    'child_id' => 6,
                    'parent_uuid' => 'c0f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b',
                    'child_uuid' => 'f0f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b',
                    'label' => 'f',
                    'weight' => 6,
                    'value' => '123.456',
                    'created_at' => Carbon::now(),
                ],
                [
                    'parent_id' => 5,
                    'child_id' => 7,
                    'parent_uuid' => 'e0f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b',
                    'child_uuid' => 'a1f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b',
                    'label' => 'g',
                    'weight' => 7,
                    'value' => '123.456',
                    'created_at' => Carbon::now(),
                ],
                [
                    'parent_id' => 5,
                    'child_id' => 8,
                    'parent_uuid' => 'e0f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b',
                    'child_uuid' => 'b1f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b',
                    'label' => 'h',
                    'weight' => 8,
                    'value' => '123.456',
                    'created_at' => Carbon::now(),
                ],
                [
                    'parent_id' => 7,
                    'child_id' => 8,
                    'parent_uuid' => 'a1f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b',
                    'child_uuid' => 'b1f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b',
                    'label' => 'i',
                    'weight' => 9,
                    'value' => '123.456',
                    'created_at' => Carbon::now(),
                ],
                [
                    'parent_id' => 9,
                    'child_id' => 2,
                    'parent_uuid' => 'c1f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b',
                    'child_uuid' => 'b0f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b',
                    'label' => 'j',
                    'weight' => 10,
                    'value' => '123.456',
                    'created_at' => Carbon::now(),
                ],
                [
                    'parent_id' => 10,
                    'child_id' => 5,
                    'parent_uuid' => 'd1f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b',
                    'child_uuid' => 'e0f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b',
                    'label' => 'k',
                    'weight' => 11,
                    'value' => '123.456',
                    'created_at' => Carbon::now(),
                ],
                [
                    'parent_id' => 11,
                    'child_id' => 5,
                    'parent_uuid' => 'e1f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b',
                    'child_uuid' => 'e0f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b',
                    'label' => 'l',
                    'weight' => 12,
                    'value' => '123.456',
                    'created_at' => Carbon::now(),
                ],
            ]
        );

        Model::reguard();
    }

    protected function seedCycle(): void
    {
        Model::unguard();

        Node::create(['slug' => 'node-12', 'uuid' => 'f1f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b']);
        Node::create(['slug' => 'node-13', 'uuid' => 'a2f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b']);
        Node::create(['slug' => 'node-14', 'uuid' => 'b2f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b']);

        DB::table('edges')->insert(
            [

                [
                    'parent_id' => 12,
                    'child_id' => 13,
                    'parent_uuid' => 'f1f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b',
                    'child_uuid' => 'a2f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b',
                    'label' => 'm',
                    'weight' => 13,
                    'value' => '123.456',
                    'created_at' => Carbon::now(),
                ],
                [
                    'parent_id' => 13,
                    'child_id' => 14,
                    'parent_uuid' => 'a2f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b',
                    'child_uuid' => 'b2f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b',
                    'label' => 'n',
                    'weight' => 14,
                    'value' => '123.456',
                    'created_at' => Carbon::now(),
                ],
                [
                    'parent_id' => 14,
                    'child_id' => 12,
                    'parent_uuid' => 'b2f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b',
                    'child_uuid' => 'f1f1b2c3-d4e5-4f6a-8b9b-0c1d2e3f4a5b',
                    'label' => 'o',
                    'weight' => 15,
                    'value' => '123.456',
                    'created_at' => Carbon::now(),
                ],
            ]
        );

        Model::reguard();
    }

    protected function getFormattedTestNow(): string
    {
        $format = Node::query()->getGrammar()->getDateFormat();

        return Carbon::getTestNow()->format($format);
    }

    public static function cycleDetectionClassProvider(): array
    {
        return [
            [NodeWithCycleDetection::class, []],
            [NodeWithUuidAndCycleDetection::class, ['sqlsrv']],
        ];
    }

    public static function cycleDetectionAndStartClassProvider(): array
    {
        return [
            [NodeWithCycleDetectionAndStart::class, []],
            [NodeWithUuidAndCycleDetectionAndStart::class, ['sqlsrv']],
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $config = require __DIR__.'/../config/database.php';

        $app['config']->set('database.default', 'testing');

        $app['config']->set('database.connections.testing', $config[$this->database]);
    }
}

<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Graph\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Expression;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasOneDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;
use Staudenmeir\EloquentHasManyDeep\HasTableAlias;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasGraphRelationships;

class Node extends Model
{
    use HasGraphRelationships {
        getCustomPaths as baseGetCustomPaths;
        getPivotColumns as baseGetPivotColumns;
    }
    use HasRelationships;
    use HasTableAlias;
    use SoftDeletes;

    public $incrementing = false;

    protected $table = 'nodes';

    protected $casts = [
        'id' => 'int',
    ];

    public function getPivotTableName(): string
    {
        return 'edges';
    }

    public function getCustomPaths(): array
    {
        return array_merge(
            $this->baseGetCustomPaths(),
            [
                [
                    'name' => 'slug_path',
                    'column' => 'slug',
                    'separator' => '/',
                ],
                [
                    'name' => 'reverse_slug_path',
                    'column' => new Expression(
                        $this->newQuery()->getGrammar()->wrap('nodes.slug')
                    ),
                    'separator' => '/',
                    'reverse' => true,
                ],
            ]
        );
    }

    public function getPivotColumns(): array
    {
        return array_merge(
            $this->baseGetPivotColumns(),
            ['label', 'weight', 'value', 'created_at']
        );
    }

    public function ancestorPost(): HasOneDeep
    {
        return $this->hasOneDeepFromRelations(
            $this->ancestors(),
            (new static())->posts()
        );
    }

    public function ancestorPosts(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations(
            $this->ancestors(),
            (new static())->posts()
        );
    }

    public function ancestorAndSelfPosts(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations(
            $this->ancestorsAndSelf(),
            (new static())->posts()
        );
    }

    public function descendantPost(): HasOneDeep
    {
        return $this->hasOneDeepFromRelations(
            $this->descendants(),
            (new static())->posts()
        );
    }

    public function descendantPosts(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations(
            $this->descendants(),
            (new static())->posts()
        );
    }

    public function descendantAndSelfPosts(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations(
            $this->descendantsAndSelf(),
            (new static())->posts()
        );
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'node_id');
    }
}

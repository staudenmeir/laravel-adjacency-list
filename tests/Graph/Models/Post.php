<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Graph\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;
use Staudenmeir\LaravelCte\Eloquent\QueriesExpressions;

class Post extends Model
{
    use HasRelationships;
    use QueriesExpressions;
    use SoftDeletes;

    public $incrementing = false;

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }

    public function nodeAncestors(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations(
            $this->node(),
            (new Node())->ancestors()
        );
    }

    public function nodeDescendants(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations(
            $this->node(),
            (new Node())->descendants()
        );
    }
}

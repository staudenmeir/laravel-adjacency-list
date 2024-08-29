<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends RootAncestor<TRelatedModel>
 */
class RootAncestorOrSelf extends RootAncestor
{
    /**
     * Create a new root ancestor or self relationship instance.
     *
     * @param \Illuminate\Database\Eloquent\Builder<TRelatedModel> $query
     * @param TRelatedModel $parent
     * @param string $foreignKey
     * @param string $localKey
     * @return void
     */
    public function __construct(Builder $query, Model $parent, string $foreignKey, string $localKey)
    {
        $this->baseConstruct($query, $parent, $foreignKey, $localKey, true);
    }
}

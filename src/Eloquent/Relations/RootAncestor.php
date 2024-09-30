<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Traits\IsAncestorRelation;

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends \Illuminate\Database\Eloquent\Relations\HasOne<TRelatedModel, TDeclaringModel>
 */
class RootAncestor extends HasOne
{
    /** @use \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Traits\IsAncestorRelation<TRelatedModel, TDeclaringModel> */
    use IsAncestorRelation {
        __construct as baseConstruct;
        addConstraints as baseAddConstraints;
    }

    /**
     * Create a new root ancestor relationship instance.
     *
     * @param \Illuminate\Database\Eloquent\Builder<TRelatedModel> $query
     * @param TDeclaringModel $parent
     * @param string $foreignKey
     * @param string $localKey
     * @return void
     */
    public function __construct(Builder $query, Model $parent, $foreignKey, $localKey)
    {
        $this->baseConstruct($query, $parent, $foreignKey, $localKey, false);
    }

    /** @inheritDoc */
    public function addConstraints()
    {
        $this->baseAddConstraints();

        $this->query->isRoot();
    }
}

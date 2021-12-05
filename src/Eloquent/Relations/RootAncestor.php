<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RootAncestor extends HasOne
{
    use IsAncestorRelation {
        __construct as baseConstruct;
        addConstraints as baseAddConstraints;
    }
    /**
     * Create a new root ancestor relationship instance.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model $parent
     * @param string $foreignKey
     * @param string $localKey
     * @return void
     */
    public function __construct(Builder $query, Model $parent, $foreignKey, $localKey)
    {
        $this->baseConstruct($query, $parent, $foreignKey, $localKey, false);
    }

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
        $this->baseAddConstraints();

        $this->query->isRoot();
    }
}

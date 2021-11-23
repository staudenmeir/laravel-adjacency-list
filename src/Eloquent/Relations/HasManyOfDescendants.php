<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HasManyOfDescendants extends HasMany
{
    use IsOfDescendantsRelation;

    /**
     * Create a new has many of descendants relationship instance.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model $parent
     * @param string $foreignKey
     * @param string $localKey
     * @param bool $andSelf
     * @return void
     */
    public function __construct(Builder $query, Model $parent, $foreignKey, $localKey, $andSelf)
    {
        $this->andSelf = $andSelf;

        parent::__construct($query, $parent, $foreignKey, $localKey);
    }

    /**
     * Set the where clause on the recursive expression query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return void
     */
    public function addExpressionWhereConstraints(Builder $query)
    {
        $column = $this->andSelf ? $this->parent->getLocalKeyName() : $this->parent->getParentKeyName();

        $query->where(
            $column,
            '=',
            $this->parent->{$this->parent->getLocalKeyName()}
        )->whereNotNull($column);
    }

    /**
     * Get the local key name for an eager load of the relation.
     *
     * @return string
     */
    public function getEagerLoadingLocalKeyName()
    {
        return $this->getLocalKeyName();
    }

    /**
     * Get the foreign key name for an eager load of the relation.
     *
     * @return string
     */
    public function getEagerLoadingForeignKeyName()
    {
        return $this->getForeignKeyName();
    }

    /**
     * Get the local key name for the recursion expression.
     *
     * @return string
     */
    public function getExpressionLocalKeyName()
    {
        return $this->getLocalKeyName();
    }

    /**
     * Get the foreign key name for the recursion expression.
     *
     * @return string
     */
    public function getExpressionForeignKeyName()
    {
        return $this->foreignKey;
    }
}

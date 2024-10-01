<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Traits\IsOfDescendantsRelation;

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends \Illuminate\Database\Eloquent\Relations\HasMany<TRelatedModel, TDeclaringModel>
 */
class HasManyOfDescendants extends HasMany
{
    /** @use \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Traits\IsOfDescendantsRelation<TRelatedModel, TDeclaringModel> */
    use IsOfDescendantsRelation;

    /**
     * Create a new has many of descendants relationship instance.
     *
     * @param \Illuminate\Database\Eloquent\Builder<TRelatedModel> $query
     * @param TDeclaringModel $parent
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
     * @param \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<\Illuminate\Database\Eloquent\Model> $query
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

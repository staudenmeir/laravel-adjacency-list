<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Traits\IsOfDescendantsRelation;

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends \Illuminate\Database\Eloquent\Relations\BelongsToMany<TRelatedModel, TDeclaringModel>
 */
class BelongsToManyOfDescendants extends BelongsToMany
{
    /** @use \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Traits\IsOfDescendantsRelation<TRelatedModel, TDeclaringModel> */
    use IsOfDescendantsRelation {
        addConstraints as baseAddConstraints;
        getRelationExistenceQuery as baseGetRelationExistenceQuery;
    }

    /**
     * Create a new belongs to many of descendants relationship instance.
     *
     * @param \Illuminate\Database\Eloquent\Builder<TRelatedModel> $query
     * @param TDeclaringModel $parent
     * @param class-string<TRelatedModel>|string $table
     * @param string $foreignPivotKey
     * @param string $relatedPivotKey
     * @param string $parentKey
     * @param string $relatedKey
     * @param bool $andSelf
     */
    public function __construct(
        Builder $query,
        Model $parent,
        $table,
        $foreignPivotKey,
        $relatedPivotKey,
        $parentKey,
        $relatedKey,
        $andSelf
    ) {
        $this->andSelf = $andSelf;

        parent::__construct($query, $parent, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey);
    }

    /** @inheritDoc */
    public function addConstraints()
    {
        $this->performJoin();

        $this->baseAddConstraints();
    }

    /**
     * Set the where clause on the recursive expression query.
     *
     * @param \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<\Illuminate\Database\Eloquent\Model> $query
     * @return void
     */
    protected function addExpressionWhereConstraints(Builder $query)
    {
        $column = $this->andSelf ? $this->parent->getLocalKeyName() : $this->parent->getParentKeyName();

        $query->where(
            $column,
            '=',
            $this->parent->{$this->parentKey}
        );
    }

    /**
     * Get the local key name for an eager load of the relation.
     *
     * @return string
     */
    public function getEagerLoadingLocalKeyName()
    {
        return $this->parentKey;
    }

    /**
     * Get the foreign key name for an eager load of the relation.
     *
     * @return string
     */
    public function getEagerLoadingForeignKeyName()
    {
        return $this->foreignPivotKey;
    }

    /**
     * Get the accessor for an eager load of the relation.
     *
     * @return string|null
     */
    public function getEagerLoadingAccessor()
    {
        return $this->accessor;
    }

    /**
     * Add the constraints for a relationship query.
     *
     * @param \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<TRelatedModel> $query
     * @param \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<TDeclaringModel> $parentQuery
     * @param list<string|\Illuminate\Database\Query\Expression>|string|\Illuminate\Database\Query\Expression $columns
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<TRelatedModel>
     */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        $this->performJoin($query);

        return $this->baseGetRelationExistenceQuery($query, $parentQuery, $columns);
    }

    /**
     * Get the local key name for the recursion expression.
     *
     * @return string
     */
    public function getExpressionLocalKeyName()
    {
        return $this->parentKey;
    }

    /**
     * Get the foreign key name for the recursion expression.
     *
     * @return string
     */
    public function getExpressionForeignKeyName()
    {
        return $this->foreignPivotKey;
    }
}

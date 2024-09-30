<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Relations;

use Illuminate\Database\Eloquent\Builder;
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
    use IsOfDescendantsRelation {
        addConstraints as baseAddConstraints;
        getRelationExistenceQuery as baseGetRelationExistenceQuery;
    }

    /**
     * Create a new belongs to many of descendants relationship instance.
     *
     * @param \Illuminate\Database\Eloquent\Builder<TRelatedModel> $query
     * @param TRelatedModel $parent
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

    /** @inheritDoc */
    protected function addExpressionWhereConstraints(Builder $query)
    {
        $column = $this->andSelf ? $this->parent->getLocalKeyName() : $this->parent->getParentKeyName();

        $query->where(
            $column,
            '=',
            $this->parent->{$this->parentKey}
        );
    }

    /** @inheritDoc */
    public function getEagerLoadingLocalKeyName()
    {
        return $this->parentKey;
    }

    /** @inheritDoc */
    public function getEagerLoadingForeignKeyName()
    {
        return $this->foreignPivotKey;
    }

    /** @inheritDoc */
    public function getEagerLoadingAccessor()
    {
        return $this->accessor;
    }

    /**
     * Add the constraints for a relationship query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Builder $parentQuery
     * @param array|mixed $columns
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        $this->performJoin($query);

        return $this->baseGetRelationExistenceQuery($query, $parentQuery, $columns);
    }

    /** @inheritDoc */
    public function getExpressionLocalKeyName()
    {
        return $this->parentKey;
    }

    /** @inheritDoc */
    public function getExpressionForeignKeyName()
    {
        return $this->foreignPivotKey;
    }
}

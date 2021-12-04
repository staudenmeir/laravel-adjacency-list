<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MorphToManyOfDescendants extends BelongsToManyOfDescendants
{
    /**
     * The type of the polymorphic relation.
     *
     * @var string
     */
    protected $morphType;

    /**
     * The class name of the morph type constraint.
     *
     * @var string
     */
    protected $morphClass;

    /**
     * Indicates if we are connecting the inverse of the relation.
     *
     * This primarily affects the morphClass constraint.
     *
     * @var bool
     */
    protected $inverse;

    /**
     * Create a new morph to many of descendants relationship instance.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model $parent
     * @param string $name
     * @param string $table
     * @param string $foreignPivotKey
     * @param string $relatedPivotKey
     * @param string $parentKey
     * @param string $relatedKey
     * @param bool $inverse
     * @param bool $andSelf
     */
    public function __construct(
        Builder $query,
        Model $parent,
        $name,
        $table,
        $foreignPivotKey,
        $relatedPivotKey,
        $parentKey,
        $relatedKey,
        $inverse,
        $andSelf
    ) {
        $this->inverse = $inverse;
        $this->morphType = $name.'_type';
        $this->morphClass = $inverse ? $query->getModel()->getMorphClass() : $parent->getMorphClass();

        parent::__construct(
            $query,
            $parent,
            $table,
            $foreignPivotKey,
            $relatedPivotKey,
            $parentKey,
            $relatedKey,
            $andSelf
        );
    }

    /**
     * Set the where clause on the recursive expression query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return void
     */
    protected function addExpressionWhereConstraints(Builder $query)
    {
        parent::addExpressionWhereConstraints($query);

        $this->query->where(
            "$this->table.$this->morphType",
            $this->morphClass
        );
    }

    /**
     * Set the where clause on the recursive expression query for an eager load of the relation.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $models
     * @return void
     */
    public function addEagerExpressionWhereConstraints(Builder $query, array $models)
    {
        parent::addEagerExpressionWhereConstraints($query, $models);

        $this->query->where(
            "$this->table.$this->morphType",
            $this->morphClass
        );
    }

    /**
     * Set the where clause on the recursive expression query for an existence query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $table
     * @return void
     */
    public function addExistenceExpressionWhereConstraints(Builder $query, $table)
    {
        parent::addExistenceExpressionWhereConstraints($query, $table);

        $this->query->where(
            "$this->table.$this->morphType",
            $this->morphClass
        );
    }
}

<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\BelongsToManyOfDescendants<TRelatedModel, TDeclaringModel>
 */
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
     * @param \Illuminate\Database\Eloquent\Builder<TRelatedModel> $query
     * @param TDeclaringModel $parent
     * @param string $name
     * @param class-string<TRelatedModel>|string $table
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
        $morphClass = $inverse ? $query->getModel()->getMorphClass() : $parent->getMorphClass();

        $this->inverse = $inverse;
        $this->morphType = $name.'_type';
        $this->morphClass = $morphClass;

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

    /** @inheritDoc */
    protected function addExpressionWhereConstraints(Builder $query)
    {
        parent::addExpressionWhereConstraints($query);

        $this->query->where(
            "$this->table.$this->morphType",
            '=',
            $this->morphClass
        );
    }

    /** @inheritDoc */
    public function addEagerExpressionWhereConstraints(Builder $query, array $models)
    {
        parent::addEagerExpressionWhereConstraints($query, $models);

        $this->query->where(
            "$this->table.$this->morphType",
            '=',
            $this->morphClass
        );
    }

    /** @inheritDoc */
    public function addExistenceExpressionWhereConstraints(Builder $query, $table)
    {
        parent::addExistenceExpressionWhereConstraints($query, $table);

        $this->query->where(
            "$this->table.$this->morphType",
            '=',
            $this->morphClass
        );
    }
}

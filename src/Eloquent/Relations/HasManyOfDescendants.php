<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Relations;

use Illuminate\Database\Eloquent\Builder;
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
    use IsOfDescendantsRelation;

    /**
     * Create a new has many of descendants relationship instance.
     *
     * @param \Illuminate\Database\Eloquent\Builder<TRelatedModel> $query
     * @param TRelatedModel $parent
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

    /** @inheritDoc */
    public function addExpressionWhereConstraints(Builder $query)
    {
        $column = $this->andSelf ? $this->parent->getLocalKeyName() : $this->parent->getParentKeyName();

        $query->where(
            $column,
            '=',
            $this->parent->{$this->parent->getLocalKeyName()}
        )->whereNotNull($column);
    }

    /** @inheritDoc */
    public function getEagerLoadingLocalKeyName()
    {
        return $this->getLocalKeyName();
    }

    /** @inheritDoc */
    public function getEagerLoadingForeignKeyName()
    {
        return $this->getForeignKeyName();
    }

    /** @inheritDoc */
    public function getExpressionLocalKeyName()
    {
        return $this->getLocalKeyName();
    }

    /** @inheritDoc */
    public function getExpressionForeignKeyName()
    {
        return $this->foreignKey;
    }
}

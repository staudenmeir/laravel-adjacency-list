<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Expression;

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 */
trait IsAncestorRelation
{
    /** @use \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Traits\IsRecursiveRelation<TRelatedModel, TDeclaringModel> */
    use IsRecursiveRelation;

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
        if (static::$constraints) {
            $constraint = function (Builder $query) {
                $key = $this->andSelf ? $this->getParentKey() : $this->getForeignKey();

                $query->where($this->getQualifiedLocalKeyName(), '=', $key);
            };

            $this->addExpression($constraint);
        }
    }

    /** @inheritDoc */
    public function addEagerConstraints(array $models)
    {
        $whereIn = $this->whereInMethod($this->parent, $this->localKey);

        $key = $this->andSelf ? $this->localKey : $this->getForeignKeyName();

        $keys = $this->getKeys($models, $key);

        $constraint = function (Builder $query) use ($whereIn, $keys) {
            $query->$whereIn($this->getQualifiedLocalKeyName(), $keys);
        };

        $this->addExpression($constraint);
    }

    /** @inheritDoc */
    protected function getKeys(array $models, $key = null)
    {
        /** @var array<int, int|string|null> $keys */
        $keys = parent::getKeys($models, $key);

        return array_filter($keys, function ($value) {
            return !is_null($value);
        });
    }

    /** @inheritDoc */
    public function matchOneOrMany(array $models, Collection $results, $relation, $type)
    {
        $dictionary = $this->buildDictionary($results);

        $attribute = $this->andSelf ? $this->localKey : $this->getForeignKeyName();

        foreach ($models as $model) {
            $key = $model->{$attribute};

            if (isset($dictionary[$key])) {
                $value = $this->getRelationValue($dictionary, $key, $type);

                $model->setRelation($relation, $value);
            }
        }

        return $models;
    }

    /** @inheritDoc */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        if ($query->getQuery()->from === $parentQuery->getQuery()->from) {
            return $this->getRelationExistenceQueryForSelfRelation($query, $parentQuery, $columns);
        }

        $key = $this->andSelf ? $this->localKey : $this->getForeignKeyName();

        $constraint = function (Builder $query) use ($key) {
            /** @var string $from */
            $from = $query->getQuery()->from;

            $query->whereColumn(
                "$from.$this->localKey",
                '=',
                $this->parent->qualifyColumn($key)
            );
        };

        $query->select($columns);

        $this->addExpression($constraint, $query);

        return $query;
    }

    /** @inheritDoc */
    public function getRelationExistenceQueryForSelfRelation(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        if ($columns instanceof Expression) {
            $columns = $this->replaceTableHash($query, $columns);
        }

        $table = $this->getRelationCountHash();

        $from = $query->getModel()->getTable().' as '.$table;

        $query->getModel()->setTable($table);

        $key = $this->andSelf ? $this->localKey : $this->getForeignKeyName();

        $constraint = function (Builder $query) use ($table, $key) {
            $query->whereColumn(
                $table.'.'.$this->localKey,
                '=',
                $this->parent->qualifyColumn($key)
            );
        };

        $query->select($columns);

        $this->addExpression($constraint, $query, $from);

        return $query;
    }

    /**
     * Add a recursive expression to the query.
     *
     * @param callable $constraint
     * @param \Illuminate\Database\Eloquent\Builder<TRelatedModel>|null $query
     * @param string|null $from
     * @return \Illuminate\Database\Eloquent\Builder<TRelatedModel>
     */
    protected function addExpression(callable $constraint, ?Builder $query = null, $from = null)
    {
        $query = $query ?: $this->query;

        $initialDepth = $this->andSelf ? 0 : -1;

        return $query->withRelationshipExpression('asc', $constraint, $initialDepth, $from);
    }

    /**
     * Get the key value of the parent's foreign key.
     *
     * @return mixed
     */
    public function getForeignKey()
    {
        return $this->parent->{$this->getForeignKeyName()};
    }
}

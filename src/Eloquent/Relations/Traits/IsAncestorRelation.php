<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Expression;

trait IsAncestorRelation
{
    use IsRecursiveRelation;

    /** @inheritDoc */
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

    /**
     * Add the constraints for a relationship query.
     *
     * @param \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<\Illuminate\Database\Eloquent\Model> $query
     * @param \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<\Illuminate\Database\Eloquent\Model> $parentQuery
     * @param array|mixed $columns
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>
     */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        if ($query->getQuery()->from === $parentQuery->getQuery()->from) {
            return $this->getRelationExistenceQueryForSelfRelation($query, $parentQuery, $columns);
        }

        $key = $this->andSelf ? $this->localKey : $this->getForeignKeyName();

        $constraint = function (Builder $query) use ($key) {
            $query->whereColumn(
                $query->getQuery()->from.'.'.$this->localKey,
                '=',
                $this->parent->qualifyColumn($key)
            );
        };

        return $this->addExpression($constraint, $query->select($columns));
    }

    /**
     * Add the constraints for a relationship query on the same table.
     *
     * @param \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<\Illuminate\Database\Eloquent\Model> $query
     * @param \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<\Illuminate\Database\Eloquent\Model> $parentQuery
     * @param array|mixed $columns
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>
     */
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

        return $this->addExpression($constraint, $query->select($columns), $from);
    }

    /**
     * Add a recursive expression to the query.
     *
     * @param callable $constraint
     * @param \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>|null $query
     * @param string|null $from
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>
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

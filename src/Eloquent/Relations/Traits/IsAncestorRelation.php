<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Expression;

trait IsAncestorRelation
{
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

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param array $models
     * @return void
     */
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

    /**
     * Get all of the primary keys for an array of models.
     *
     * @param array $models
     * @param string $key
     * @return array
     */
    protected function getKeys(array $models, $key = null)
    {
        $keys = parent::getKeys($models, $key);

        return array_filter($keys, function ($value) {
            return !is_null($value);
        });
    }

    /**
     * Match the eagerly loaded results to their many parents.
     *
     * @param array $models
     * @param \Illuminate\Database\Eloquent\Collection $results
     * @param string $relation
     * @param string $type
     * @return array
     */
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
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Builder $parentQuery
     * @param array|mixed $columns
     * @return \Illuminate\Database\Eloquent\Builder
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
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Builder $parentQuery
     * @param array|mixed $columns
     * @return \Illuminate\Database\Eloquent\Builder
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
     * @param \Illuminate\Database\Eloquent\Builder|null $query
     * @param string|null $from
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function addExpression(callable $constraint, Builder $query = null, $from = null)
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

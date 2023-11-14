<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Expression;
use Staudenmeir\EloquentHasManyDeepContracts\Interfaces\ConcatenableRelation;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Traits\Concatenation\IsConcatenableDescendantsRelation;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Traits\IsRecursiveRelation;

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @extends HasMany<TRelatedModel>
 */
class Descendants extends HasMany implements ConcatenableRelation
{
    use IsConcatenableDescendantsRelation;
    use IsRecursiveRelation {
        buildDictionary as baseBuildDictionary;
    }

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
        if (static::$constraints) {
            $constraint = function (Builder $query) {
                if ($this->andSelf) {
                    $query->where($this->getQualifiedLocalKeyName(), '=', $this->getParentKey());
                } else {
                    $query->where($this->foreignKey, '=', $this->getParentKey())
                        ->whereNotNull($this->foreignKey);
                }
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

        $column = $this->andSelf ? $this->getQualifiedLocalKeyName() : $this->foreignKey;

        $keys = $this->getKeys($models, $this->localKey);

        $constraint = function (Builder $query) use ($whereIn, $column, $keys) {
            $query->$whereIn($column, $keys);
        };

        $this->addExpression($constraint);
    }

    /**
     * Match the eagerly loaded results to their parents.
     *
     * @param array $models
     * @param \Illuminate\Database\Eloquent\Collection $results
     * @param string $relation
     * @return array
     */
    public function match(array $models, Collection $results, $relation)
    {
        $dictionary = $this->buildDictionary($results);

        foreach ($models as $model) {
            $key = $model->{$this->localKey};

            if (isset($dictionary[$key])) {
                $value = $this->related->newCollection($dictionary[$key]);

                $model->setRelation($relation, $value);
            }
        }

        return $models;
    }

    /**
     * Build model dictionary.
     *
     * @param \Illuminate\Database\Eloquent\Collection $results
     * @return array
     */
    protected function buildDictionary(Collection $results)
    {
        if ($this->andSelf) {
            return $this->baseBuildDictionary($results);
        }

        $dictionary = $results->keyBy($this->localKey);

        $foreignKey = $this->getForeignKeyName();

        return $results->mapToDictionary(function (Model $result) use ($dictionary, $foreignKey) {
            if ($result->hasNestedPath()) {
                $key = $dictionary[$result->getFirstPathSegment()]->{$foreignKey};
            } else {
                $key = $result->{$foreignKey};
            }

            return [$key => $result];
        })->all();
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

        $first = $this->andSelf
            ? $query->getQuery()->from.'.'.$this->localKey
            : $this->foreignKey;

        $constraint = function (Builder $query) use ($first) {
            $query->whereColumn(
                $first,
                '=',
                $this->getQualifiedParentKeyName()
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

        $first = $this->andSelf
            ? $table.'.'.$this->localKey
            : $table.'.'.$this->getForeignKeyName();

        $constraint = function (Builder $query) use ($first) {
            $query->whereColumn(
                $first,
                '=',
                $this->getQualifiedParentKeyName()
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

        $initialDepth = $this->andSelf ? 0 : 1;

        return $query->withRelationshipExpression('desc', $constraint, $initialDepth, $from);
    }
}

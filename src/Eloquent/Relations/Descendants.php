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
 *
 * @extends HasMany<TRelatedModel>
 */
class Descendants extends HasMany implements ConcatenableRelation
{
    use IsConcatenableDescendantsRelation;
    use IsRecursiveRelation {
        buildDictionary as baseBuildDictionary;
    }

    /** @inheritDoc */
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

    /** @inheritDoc */
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

    /** @inheritDoc */
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

    /** @inheritDoc */
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

    /** @inheritDoc */
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

    /** @inheritDoc */
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

    /** @inheritDoc */
    protected function addExpression(callable $constraint, ?Builder $query = null, $from = null)
    {
        $query = $query ?: $this->query;

        $initialDepth = $this->andSelf ? 0 : 1;

        return $query->withRelationshipExpression('desc', $constraint, $initialDepth, $from);
    }
}

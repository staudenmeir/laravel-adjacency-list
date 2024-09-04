<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Graph;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Expression;
use Staudenmeir\EloquentHasManyDeepContracts\Interfaces\ConcatenableRelation;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Graph\Traits\Concatenation\IsConcatenableDescendantsRelation;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Graph\Traits\IsRecursiveRelation;

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends BelongsToMany<TRelatedModel>
 */
class Descendants extends BelongsToMany implements ConcatenableRelation
{
    use IsConcatenableDescendantsRelation;
    use IsRecursiveRelation {
        buildDictionary as baseBuildDictionary;
    }

    /** @inheritDoc */
    public function addConstraints()
    {
        if (static::$constraints) {
            $column = $this->andSelf ? $this->getQualifiedParentKeyName() : $this->getQualifiedForeignPivotKeyName();

            $constraint = function (Builder $query) use ($column) {
                $query->where(
                    $column,
                    '=',
                    $this->parent->{$this->parentKey}
                );
            };

            $this->addExpression($constraint);
        }
    }

    /** @inheritDoc */
    public function addEagerConstraints(array $models)
    {
        $column = $this->andSelf ? $this->getQualifiedParentKeyName() : $this->getQualifiedForeignPivotKeyName();

        $this->addEagerExpression($models, $column);
    }

    /**
     * Build model dictionary.
     *
     * @param \Illuminate\Database\Eloquent\Collection<array-key, \Illuminate\Database\Eloquent\Model> $results
     * @return array<string, \Illuminate\Database\Eloquent\Model[]>
     */
    protected function buildDictionary(Collection $results)
    {
        if ($this->andSelf) {
            return $this->baseBuildDictionary($results);
        }

        $dictionary = [];

        $depthName = $this->related->getDepthName();

        $firstLevelResults = $results->where($depthName, '=', 1)->groupBy($this->parentKey);

        foreach ($results as $result) {
            $keys = [];

            if ($result->$depthName > 1) {
                foreach ($firstLevelResults[$result->getFirstPathSegment()] as $model) {
                    $keys[] = $model->{$this->accessor}->{$this->foreignPivotKey};
                }
            } else {
                $keys[] = $result->{$this->accessor}->{$this->foreignPivotKey};
            }

            foreach ($keys as $key) {
                $dictionary[$key][] = $result;
            }
        }

        return $dictionary;
    }

    /** @inheritDoc */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        if ($query->getQuery()->from === $parentQuery->getQuery()->from) {
            return $this->getRelationExistenceQueryForSelfRelation($query, $columns);
        }

        $first = $this->andSelf
            ? $query->getQuery()->from . '.' . $this->parentKey
            : $this->getQualifiedForeignPivotKeyName();

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
     * @param array|mixed $columns
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationExistenceQueryForSelfRelation(
        Builder $query,
        $columns = ['*']
    ): Builder {
        if ($columns instanceof Expression) {
            $columns = $this->replaceTableHash($query, $columns);
        }

        $table = $this->getRelationCountHash();

        $from = $query->getModel()->getTable() . ' as ' . $table;

        $query->getModel()->setTable($table);

        $first = $this->andSelf
            ? "$table.$this->parentKey"
            : "$this->table.$this->foreignPivotKey";

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
     * @param string $union
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function addExpression(
        callable $constraint,
        ?Builder $query = null,
        ?string $from = null,
        string $union = 'unionAll'
    ): Builder {
        $query = $query ?: $this->query;

        $initialDepth = $this->andSelf ? 0 : 1;

        return $query->withRelationshipExpression('desc', $constraint, $initialDepth, $from, null, $union);
    }
}

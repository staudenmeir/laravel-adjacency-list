<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Graph\Traits\Concatenation;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\PostgresConnection;
use RuntimeException;

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 */
trait IsConcatenableRelation
{
    /**
     * Append the relation's through parents, foreign and local keys to a deep relationship.
     *
     * @param non-empty-list<string> $through
     * @param non-empty-list<array{0: string, 1: string}|callable|string|\Staudenmeir\EloquentHasManyDeep\Eloquent\CompositeKey|null> $foreignKeys
     * @param non-empty-list<array{0: string, 1: string}|callable|string|\Staudenmeir\EloquentHasManyDeep\Eloquent\CompositeKey|null> $localKeys
     * @param int $position
     * @return array{0: non-empty-list<string>,
     *     1: non-empty-list<array{0: string, 1: string}|callable|string|\Staudenmeir\EloquentHasManyDeep\Eloquent\CompositeKey|null>,
     *     2: non-empty-list<array{0: string, 1: string}|callable|string|\Staudenmeir\EloquentHasManyDeep\Eloquent\CompositeKey|null>}
     */
    public function appendToDeepRelationship(array $through, array $foreignKeys, array $localKeys, int $position): array
    {
        if ($position === 0) {
            $foreignKeys[] = function (Builder $query, ?Builder $parentQuery = null) {
                if ($parentQuery) {
                    $this->getRelationExistenceQuery($this->query, $parentQuery);
                }

                $this->mergeExpressions($query, $this->query);
            };

            $localKeys[] = null;
        } else {
            throw new RuntimeException(
                sprintf(
                    '%s can only be at the beginning of deep relationships at the moment.',
                    class_basename($this)
                )
            );
        }

        return [$through, $foreignKeys, $localKeys];
    }

    /**
     * Get the related table name for a deep relationship.
     *
     * @return string
     */
    public function getTableForDeepRelationship(): string
    {
        return $this->related->getExpressionName();
    }

    /**
     * The custom callback to run at the end of the get() method.
     *
     * @param \Illuminate\Database\Eloquent\Collection<int, TRelatedModel> $models
     * @return void
     */
    public function postGetCallback(Collection $models): void
    {
        if (!$this->query->getConnection() instanceof PostgresConnection) {
            return;
        }

        if (!isset($models[0]->laravel_through_key)) {
            return;
        }

        $this->replacePathSeparator(
            $models,
            'laravel_through_key',
            $this->related->getPathSeparator()
        );
    }

    /**
     * Replace the separator in a PostgreSQL path column.
     *
     * @param \Illuminate\Database\Eloquent\Collection<int, TRelatedModel> $models
     * @param string $column
     * @param string $separator
     * @return void
     */
    protected function replacePathSeparator(Collection $models, string $column, string $separator): void
    {
        foreach ($models as $model) {
            $model->$column = str_replace(
                ',',
                $separator,
                substr($model->$column, 1, -1)
            );
        }
    }

    /**
     * Set the constraints for an eager load of the deep relation.
     *
     * @param \Illuminate\Database\Eloquent\Builder<TRelatedModel> $query
     * @param list<TDeclaringModel> $models
     * @return void
     */
    public function addEagerConstraintsToDeepRelationship(Builder $query, array $models): void
    {
        $this->addEagerConstraints($models);

        $this->mergeExpressions($query, $this->query);

        $query->getQuery()->distinct = $this->query->getQuery()->distinct;
    }

    /**
     * Merge the common table expressions from one query into another.
     *
     * @param \Illuminate\Database\Eloquent\Builder<*> $query
     * @param \Illuminate\Database\Eloquent\Builder<*> $from
     * @return \Illuminate\Database\Eloquent\Builder<*>
     */
    protected function mergeExpressions(Builder $query, Builder $from): Builder
    {
        /** @var \Staudenmeir\LaravelCte\Query\Builder $baseQuery */
        $baseQuery = $query->getQuery();

        /** @var \Staudenmeir\LaravelCte\Query\Builder $fromQuery */
        $fromQuery = $from->getQuery();

        $baseQuery->expressions = array_merge(
            $baseQuery->expressions,
            $fromQuery->expressions
        );

        $query->addBinding(
            $fromQuery->getRawBindings()['expressions'],
            'expressions'
        );

        return $query;
    }

    /**
     * Match the eagerly loaded results for a deep relationship to their parents.
     *
     * @param list<\Illuminate\Database\Eloquent\Model> $models
     * @param \Illuminate\Database\Eloquent\Collection<array-key, \Illuminate\Database\Eloquent\Model> $results
     * @param string $relation
     * @param string $type
     * @return list<\Illuminate\Database\Eloquent\Model>
     */
    public function matchResultsForDeepRelationship(
        array $models,
        Collection $results,
        string $relation,
        string $type = 'many'
    ): array {
        $dictionary = $this->buildDictionaryForDeepRelationship($results);

        $attribute = $this->parentKey;

        foreach ($models as $model) {
            $key = $model->$attribute;

            if (isset($dictionary[$key])) {
                $value = $dictionary[$key];

                $value = $type === 'one' ? reset($value) : $this->related->newCollection($value);

                $model->setRelation($relation, $value);
            }
        }

        return $models;
    }

    /**
     * Build the model dictionary for a deep relation.
     *
     * @param \Illuminate\Database\Eloquent\Collection<array-key, \Illuminate\Database\Eloquent\Model> $results
     * @return array<int|string, list<\Illuminate\Database\Eloquent\Model>>
     */
    protected function buildDictionaryForDeepRelationship(Collection $results): array
    {
        $pathSeparator = $this->related->getPathSeparator();

        if ($this->andSelf) {
            return $results->mapToDictionary(function (Model $result) use ($pathSeparator) {
                return [strtok($result->laravel_through_key, $pathSeparator) => $result];
            })->all();
        }

        $dictionary = [];

        $firstLevelResults = $results->filter(
            fn (Model $result) => !str_contains($result->laravel_through_key, $pathSeparator)
        )->groupBy('laravel_through_key');

        foreach ($results as $result) {
            $keys = [];

            if (str_contains($result->laravel_through_key, $pathSeparator)) {
                $firstPathSegment = strtok($result->laravel_through_key, $pathSeparator);

                foreach ($firstLevelResults[$firstPathSegment] as $model) {
                    $keys[] = $model->laravel_through_key_pivot_id;
                }
            } else {
                $keys[] = $result->laravel_through_key_pivot_id;
            }

            foreach ($keys as $key) {
                $dictionary[$key][] = $result;
            }
        }

        return $dictionary;
    }
}

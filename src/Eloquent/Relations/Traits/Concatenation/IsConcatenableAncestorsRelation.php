<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Traits\Concatenation;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 */
trait IsConcatenableAncestorsRelation
{
    use IsConcatenableRelation;

    /**
     * Set the constraints for an eager load of the deep relation.
     *
     * @param \Illuminate\Database\Eloquent\Builder<*> $query
     * @param list<TDeclaringModel> $models
     * @return void
     */
    public function addEagerConstraintsToDeepRelationship(Builder $query, array $models): void
    {
        $this->addEagerConstraints($models);

        $this->mergeExpressions($query, $this->query);
    }

    /**
     * Match the eagerly loaded results for a deep relationship to their parents.
     *
     * @param list<\Illuminate\Database\Eloquent\Model> $models
     * @param \Illuminate\Database\Eloquent\Collection<array-key, \Illuminate\Database\Eloquent\Model> $results
     * @param string $relation
     * @param string $type
     * @return list<\Illuminate\Database\Eloquent\Model> $models
     */
    public function matchResultsForDeepRelationship(
        array $models,
        Collection $results,
        string $relation,
        string $type = 'many'
    ): array {
        $dictionary = $this->buildDictionaryForDeepRelationship($results);

        $attribute = $this->andSelf ? $this->localKey : $this->getForeignKeyName();

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

        return $results->mapToDictionary(function (Model $result) use ($pathSeparator) {
            $key = strtok($result->laravel_through_key, $pathSeparator);

            return [$key => $result];
        })->all();
    }
}

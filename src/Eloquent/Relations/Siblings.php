<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends \Illuminate\Database\Eloquent\Relations\HasMany<TRelatedModel, TDeclaringModel>
 */
class Siblings extends HasMany
{
    /**
     * Whether to include the parent model.
     *
     * @var bool
     */
    protected $andSelf;

    /**
     * Create a new siblings relationship instance.
     *
     * @param \Illuminate\Database\Eloquent\Builder<TRelatedModel> $query
     * @param TDeclaringModel $parent
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
    public function addConstraints()
    {
        if (static::$constraints) {
            $this->query->where($this->foreignKey, '=', $this->getParentKey());

            if (!$this->andSelf) {
                $this->query->where(
                    $this->related->getQualifiedLocalKeyName(),
                    '<>',
                    $this->parent->{$this->parent->getLocalKeyName()}
                );
            }

            if (!array_key_exists($this->localKey, $this->parent->getAttributes())) {
                $this->query->whereNotNull($this->foreignKey);
            }
        }
    }

    /** @inheritDoc */
    public function addEagerConstraints(array $models)
    {
        $keys = $this->getKeys($models, $this->localKey);

        $this->query->where(
            function (Builder $query) use ($keys) {
                $query->whereIn($this->foreignKey, $keys);

                if (in_array(null, $keys, true)) {
                    $query->orWhereNull($this->foreignKey);
                }
            }
        );
    }

    /** @inheritDoc */
    public function match(array $models, Collection $results, $relation)
    {
        $dictionary = $this->buildDictionary($results);

        foreach ($models as $model) {
            $key = $model->{$this->localKey};

            if (isset($dictionary[$key])) {
                $value = $this->related->newCollection($dictionary[$key]);

                if (!$this->andSelf) {
                    $value = $value->reject(function (Model $result) use ($model) {
                        return $result->{$result->getLocalKeyName()} == $model->{$model->getLocalKeyName()};
                    })->values();
                }

                $model->setRelation($relation, $value);
            }
        }

        return $models;
    }

    /** @inheritDoc */
    public function getResults()
    {
        return $this->query->get();
    }

    /** @inheritDoc */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        if ($query->getQuery()->from === $parentQuery->getQuery()->from) {
            return $this->getRelationExistenceQueryForSelfRelation($query, $parentQuery, $columns);
        }

        $first = $this->foreignKey;
        $second = $parentQuery->qualifyColumn($this->localKey);

        $query->select($columns)
            ->where(function (Builder $query) use ($first, $second) {
                $query->whereColumn($first, '=', $second)
                    ->orWhere(function (Builder $query) use ($first, $second) {
                        $query->whereNull($first)->whereNull($second);
                    });
            });

        if (!$this->andSelf) {
            $query->whereColumn(
                $this->related->getQualifiedLocalKeyName(),
                '<>',
                $this->parent->getQualifiedLocalKeyName()
            );
        }

        return $query;
    }

    /** @inheritDoc */
    public function getRelationExistenceQueryForSelfRelation(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        $table = $this->getRelationCountHash();

        $query->from($query->getModel()->getTable().' as '.$table);

        $query->getModel()->setTable($table);

        $first = $table.'.'.$this->getForeignKeyName();
        $second = $this->getQualifiedParentKeyName();

        $query->select($columns)
            ->where(function (Builder $query) use ($first, $second) {
                $query->whereColumn($first, '=', $second)
                    ->orWhere(function (Builder $query) use ($first, $second) {
                        $query->whereNull($first)->whereNull($second);
                    });
            });

        if (!$this->andSelf) {
            $query->whereColumn(
                $table.'.'.$this->related->getLocalKeyName(),
                '<>',
                $this->parent->getQualifiedLocalKeyName()
            );
        }

        return $query;
    }
}

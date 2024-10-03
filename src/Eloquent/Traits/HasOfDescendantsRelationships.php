<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\BelongsToManyOfDescendants;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\HasManyOfDescendants;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\MorphToManyOfDescendants;

trait HasOfDescendantsRelationships
{
    /**
     * Define a one-to-many relationship of the model's descendants.
     *
     * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
     *
     * @param class-string<TRelatedModel> $related
     * @param string|null $foreignKey
     * @param string|null $localKey
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\HasManyOfDescendants<TRelatedModel>
     */
    public function hasManyOfDescendants($related, $foreignKey = null, $localKey = null)
    {
        /** @var TRelatedModel $instance */
        $instance = $this->newRelatedInstance($related);

        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();

        return $this->newHasManyOfDescendants(
            $instance->newQuery(),
            $this,
            $instance->qualifyColumn($foreignKey),
            $localKey,
            false
        );
    }

    /**
     * Define a one-to-many relationship of the model's descendants and itself.
     *
     * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
     *
     * @param class-string<TRelatedModel> $related
     * @param string|null $foreignKey
     * @param string|null $localKey
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\HasManyOfDescendants<TRelatedModel>
     */
    public function hasManyOfDescendantsAndSelf($related, $foreignKey = null, $localKey = null)
    {
        /** @var TRelatedModel $instance */
        $instance = $this->newRelatedInstance($related);

        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();

        return $this->newHasManyOfDescendants(
            $instance->newQuery(),
            $this,
            $instance->qualifyColumn($foreignKey),
            $localKey,
            true
        );
    }

    /**
     * Instantiate a new HasManyOfDescendants relationship.
     *
     * @template TRelatedModel of Model
     *
     * @param \Illuminate\Database\Eloquent\Builder<TRelatedModel> $query
     * @param TRelatedModel $parent
     * @param string $foreignKey
     * @param string $localKey
     * @param bool $andSelf
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\HasManyOfDescendants<TRelatedModel>
     */
    protected function newHasManyOfDescendants(Builder $query, Model $parent, $foreignKey, $localKey, $andSelf)
    {
        return new HasManyOfDescendants($query, $parent, $foreignKey, $localKey, $andSelf);
    }

    /**
     * Define a many-to-many relationship of the model's descendants.
     *
     * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
     *
     * @param class-string<TRelatedModel> $related
     * @param class-string<TRelatedModel>|string|null $table
     * @param string|null $foreignPivotKey
     * @param string|null $relatedPivotKey
     * @param string|null $parentKey
     * @param string|null $relatedKey
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\BelongsToManyOfDescendants<TRelatedModel>
     */
    public function belongsToManyOfDescendants(
        $related,
        $table = null,
        $foreignPivotKey = null,
        $relatedPivotKey = null,
        $parentKey = null,
        $relatedKey = null
    ) {
        /** @var TRelatedModel $instance */
        $instance = $this->newRelatedInstance($related);

        $foreignPivotKey = $foreignPivotKey ?: $this->getForeignKey();

        $relatedPivotKey = $relatedPivotKey ?: $instance->getForeignKey();

        if (is_null($table)) {
            $table = $this->joiningTable($related, $instance);
        }

        return $this->newBelongsToManyOfDescendants(
            $instance->newQuery(),
            $this,
            $table,
            $foreignPivotKey,
            $relatedPivotKey,
            $parentKey ?: $this->getKeyName(),
            $relatedKey ?: $instance->getKeyName(),
            false
        );
    }

    /**
     * Define a many-to-many relationship of the model's descendants and itself.
     *
     * @template TRelatedModel of Model
     *
     * @param class-string<TRelatedModel> $related
     * @param class-string<TRelatedModel>|string|null $table
     * @param string|null $foreignPivotKey
     * @param string|null $relatedPivotKey
     * @param string|null $parentKey
     * @param string|null $relatedKey
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\BelongsToManyOfDescendants<TRelatedModel>
     */
    public function belongsToManyOfDescendantsAndSelf(
        $related,
        $table = null,
        $foreignPivotKey = null,
        $relatedPivotKey = null,
        $parentKey = null,
        $relatedKey = null
    ) {
        /** @var TRelatedModel $instance */
        $instance = $this->newRelatedInstance($related);

        $foreignPivotKey = $foreignPivotKey ?: $this->getForeignKey();

        $relatedPivotKey = $relatedPivotKey ?: $instance->getForeignKey();

        if (is_null($table)) {
            $table = $this->joiningTable($related, $instance);
        }

        return $this->newBelongsToManyOfDescendants(
            $instance->newQuery(),
            $this,
            $table,
            $foreignPivotKey,
            $relatedPivotKey,
            $parentKey ?: $this->getKeyName(),
            $relatedKey ?: $instance->getKeyName(),
            true
        );
    }

    /**
     * Instantiate a new BelongsToManyOfDescendants relationship.
     *
     * @template TRelatedModel of Model
     *
     * @param \Illuminate\Database\Eloquent\Builder<TRelatedModel> $query
     * @param \Illuminate\Database\Eloquent\Model $parent
     * @param class-string<TRelatedModel>|string $table
     * @param string $foreignPivotKey
     * @param string $relatedPivotKey
     * @param string $parentKey
     * @param string $relatedKey
     * @param bool $andSelf
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\BelongsToManyOfDescendants<TRelatedModel>
     */
    protected function newBelongsToManyOfDescendants(
        Builder $query,
        Model $parent,
        $table,
        $foreignPivotKey,
        $relatedPivotKey,
        $parentKey,
        $relatedKey,
        $andSelf
    ) {
        return new BelongsToManyOfDescendants(
            $query,
            $parent,
            $table,
            $foreignPivotKey,
            $relatedPivotKey,
            $parentKey,
            $relatedKey,
            $andSelf
        );
    }

    /**
     * Define a polymorphic many-to-many relationship of the model's descendants.
     *
     * @template TRelatedModel of Model
     *
     * @param class-string<TRelatedModel> $related
     * @param string $name
     * @param string|null $table
     * @param string|null $foreignPivotKey
     * @param string|null $relatedPivotKey
     * @param string|null $parentKey
     * @param string|null $relatedKey
     * @param bool $inverse
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\MorphToManyOfDescendants<TRelatedModel>
     */
    public function morphToManyOfDescendants(
        $related,
        $name,
        $table = null,
        $foreignPivotKey = null,
        $relatedPivotKey = null,
        $parentKey = null,
        $relatedKey = null,
        $inverse = false
    ) {
        /** @var TRelatedModel $instance */
        $instance = $this->newRelatedInstance($related);

        $foreignPivotKey = $foreignPivotKey ?: $name.'_id';

        $relatedPivotKey = $relatedPivotKey ?: $instance->getForeignKey();

        if (!$table) {
            $words = preg_split('/(_)/u', $name, -1, PREG_SPLIT_DELIM_CAPTURE);

            $lastWord = array_pop($words);

            $table = implode('', $words).Str::plural($lastWord);
        }

        return $this->newMorphToManyOfDescendants(
            $instance->newQuery(),
            $this,
            $name,
            $table,
            $foreignPivotKey,
            $relatedPivotKey,
            $parentKey ?: $this->getKeyName(),
            $relatedKey ?: $instance->getKeyName(),
            $inverse,
            false
        );
    }

    /**
     * Define a polymorphic many-to-many relationship of the model's descendants and itself.
     *
     * @template TRelatedModel of Model
     *
     * @param class-string<TRelatedModel> $related
     * @param string $name
     * @param string|null $table
     * @param string|null $foreignPivotKey
     * @param string|null $relatedPivotKey
     * @param string|null $parentKey
     * @param string|null $relatedKey
     * @param bool $inverse
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\MorphToManyOfDescendants<TRelatedModel>
     */
    public function morphToManyOfDescendantsAndSelf(
        $related,
        $name,
        $table = null,
        $foreignPivotKey = null,
        $relatedPivotKey = null,
        $parentKey = null,
        $relatedKey = null,
        $inverse = false
    ) {
        /** @var TRelatedModel $instance */
        $instance = $this->newRelatedInstance($related);

        $foreignPivotKey = $foreignPivotKey ?: $name.'_id';

        $relatedPivotKey = $relatedPivotKey ?: $instance->getForeignKey();

        if (!$table) {
            $words = preg_split('/(_)/u', $name, -1, PREG_SPLIT_DELIM_CAPTURE);

            $lastWord = array_pop($words);

            $table = implode('', $words).Str::plural($lastWord);
        }

        return $this->newMorphToManyOfDescendants(
            $instance->newQuery(),
            $this,
            $name,
            $table,
            $foreignPivotKey,
            $relatedPivotKey,
            $parentKey ?: $this->getKeyName(),
            $relatedKey ?: $instance->getKeyName(),
            $inverse,
            true
        );
    }

    /**
     * Instantiate a new MorphToManyOfDescendants relationship.
     *
     * @template TRelatedModel of Model
     *
     * @param \Illuminate\Database\Eloquent\Builder<TRelatedModel> $query
     * @param \Illuminate\Database\Eloquent\Model $parent
     * @param string $name
     * @param class-string<TRelatedModel>|string $table
     * @param string $foreignPivotKey
     * @param string $relatedPivotKey
     * @param string $parentKey
     * @param string $relatedKey
     * @param bool $inverse
     * @param bool $andSelf
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\MorphToManyOfDescendants<TRelatedModel>
     */
    protected function newMorphToManyOfDescendants(
        Builder $query,
        Model $parent,
        $name,
        $table,
        $foreignPivotKey,
        $relatedPivotKey,
        $parentKey,
        $relatedKey,
        $inverse,
        $andSelf
    ) {
        return new MorphToManyOfDescendants(
            $query,
            $parent,
            $name,
            $table,
            $foreignPivotKey,
            $relatedPivotKey,
            $parentKey,
            $relatedKey,
            $inverse,
            $andSelf
        );
    }

    /**
     * Define a polymorphic, inverse many-to-many relationship of the model's descendants.
     *
     * @template TRelatedModel of Model
     *
     * @param class-string<TRelatedModel> $related
     * @param string $name
     * @param string|null $table
     * @param string|null $foreignPivotKey
     * @param string|null $relatedPivotKey
     * @param string|null $parentKey
     * @param string|null $relatedKey
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\MorphToManyOfDescendants<TRelatedModel>
     */
    public function morphedByManyOfDescendants(
        $related,
        $name,
        $table = null,
        $foreignPivotKey = null,
        $relatedPivotKey = null,
        $parentKey = null,
        $relatedKey = null
    ) {
        $foreignPivotKey = $foreignPivotKey ?: $this->getForeignKey();

        $relatedPivotKey = $relatedPivotKey ?: $name.'_id';

        return $this->morphToManyOfDescendants(
            $related,
            $name,
            $table,
            $foreignPivotKey,
            $relatedPivotKey,
            $parentKey,
            $relatedKey,
            true
        );
    }

    /**
     * Define a polymorphic, inverse many-to-many relationship of the model's descendants and itself.
     *
     * @template TRelatedModel of Model
     *
     * @param class-string<TRelatedModel> $related
     * @param string $name
     * @param string|null $table
     * @param string|null $foreignPivotKey
     * @param string|null $relatedPivotKey
     * @param string|null $parentKey
     * @param string|null $relatedKey
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\MorphToManyOfDescendants<TRelatedModel>
     */
    public function morphedByManyOfDescendantsAndSelf(
        $related,
        $name,
        $table = null,
        $foreignPivotKey = null,
        $relatedPivotKey = null,
        $parentKey = null,
        $relatedKey = null
    ) {
        $foreignPivotKey = $foreignPivotKey ?: $this->getForeignKey();

        $relatedPivotKey = $relatedPivotKey ?: $name.'_id';

        return $this->morphToManyOfDescendantsAndSelf(
            $related,
            $name,
            $table,
            $foreignPivotKey,
            $relatedPivotKey,
            $parentKey,
            $relatedKey,
            true
        );
    }
}

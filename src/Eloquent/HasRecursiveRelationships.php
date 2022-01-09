<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Ancestors;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Bloodline;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Descendants;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\RootAncestor;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Siblings;
use Staudenmeir\LaravelCte\Eloquent\QueriesExpressions;

trait HasRecursiveRelationships
{
    use HasOfDescendantsRelationships;
    use HasRecursiveRelationshipScopes;
    use QueriesExpressions;

    /**
     * The additional constraint for the recursive query.
     *
     * @var callable|null
     */
    public static $recursiveQueryConstraint;

    /**
     * Get the name of the parent key column.
     *
     * @return string
     */
    public function getParentKeyName()
    {
        return 'parent_id';
    }

    /**
     * Get the qualified parent key column.
     *
     * @return string
     */
    public function getQualifiedParentKeyName()
    {
        return (new static())->getTable().'.'.$this->getParentKeyName();
    }

    /**
     * Get the name of the local key column.
     *
     * @return string
     */
    public function getLocalKeyName()
    {
        return $this->getKeyName();
    }

    /**
     * Get the qualified local key column.
     *
     * @return string
     */
    public function getQualifiedLocalKeyName()
    {
        return $this->qualifyColumn($this->getLocalKeyName());
    }

    /**
     * Get the name of the depth column.
     *
     * @return string
     */
    public function getDepthName()
    {
        return 'depth';
    }

    /**
     * Get the name of the path column.
     *
     * @return string
     */
    public function getPathName()
    {
        return 'path';
    }

    /**
     * Get the path separator.
     *
     * @return string
     */
    public function getPathSeparator()
    {
        return '.';
    }

    /**
     * Get the additional custom paths.
     *
     * @return array
     */
    public function getCustomPaths()
    {
        return [];
    }

    /**
     * Get the name of the common table expression.
     *
     * @return string
     */
    public function getExpressionName()
    {
        return 'laravel_cte';
    }

    /**
     * Get the model's ancestors.
     *
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Ancestors
     */
    public function ancestors()
    {
        return $this->newAncestors(
            (new static())->newQuery(),
            $this,
            $this->getQualifiedParentKeyName(),
            $this->getLocalKeyName(),
            false
        );
    }

    /**
     * Get the model's ancestors and itself.
     *
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Ancestors
     */
    public function ancestorsAndSelf()
    {
        return $this->newAncestors(
            (new static())->newQuery(),
            $this,
            $this->getQualifiedParentKeyName(),
            $this->getLocalKeyName(),
            true
        );
    }

    /**
     * Instantiate a new Ancestors relationship.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model $parent
     * @param string $foreignKey
     * @param string $localKey
     * @param bool $andSelf
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Ancestors
     */
    protected function newAncestors(Builder $query, Model $parent, $foreignKey, $localKey, $andSelf)
    {
        return new Ancestors($query, $parent, $foreignKey, $localKey, $andSelf);
    }

    /**
     * Get the model's bloodline.
     *
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Bloodline
     */
    public function bloodline()
    {
        return $this->newBloodline(
            (new static())->newQuery(),
            $this,
            $this->getQualifiedParentKeyName(),
            $this->getLocalKeyName()
        );
    }

    /**
     * Instantiate a new Bloodline relationship.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model $parent
     * @param string $foreignKey
     * @param string $localKey
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Bloodline
     */
    protected function newBloodline(Builder $query, Model $parent, $foreignKey, $localKey)
    {
        return new Bloodline($query, $parent, $foreignKey, $localKey);
    }

    /**
     * Get the model's children.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children()
    {
        return $this->hasMany(static::class, $this->getParentKeyName(), $this->getLocalKeyName());
    }

    /**
     * Get the model's children and itself.
     *
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Descendants
     */
    public function childrenAndSelf()
    {
        return $this->descendantsAndSelf()->whereDepth('<=', 1);
    }

    /**
     * Get the model's descendants.
     *
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Descendants
     */
    public function descendants()
    {
        return $this->newDescendants(
            (new static())->newQuery(),
            $this,
            $this->getQualifiedParentKeyName(),
            $this->getLocalKeyName(),
            false
        );
    }

    /**
     * Get the model's descendants and itself.
     *
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Descendants
     */
    public function descendantsAndSelf()
    {
        return $this->newDescendants(
            (new static())->newQuery(),
            $this,
            $this->getQualifiedParentKeyName(),
            $this->getLocalKeyName(),
            true
        );
    }

    /**
     * Instantiate a new Descendants relationship.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model $parent
     * @param string $foreignKey
     * @param string $localKey
     * @param bool $andSelf
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Descendants
     */
    protected function newDescendants(Builder $query, Model $parent, $foreignKey, $localKey, $andSelf)
    {
        return new Descendants($query, $parent, $foreignKey, $localKey, $andSelf);
    }

    /**
     * Get the model's parent.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(static::class, $this->getParentKeyName(), $this->getLocalKeyName());
    }

    /**
     * Get the model's parent and itself.
     *
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Ancestors
     */
    public function parentAndSelf()
    {
        return $this->ancestorsAndSelf()->whereDepth('>=', -1);
    }

    /**
     * Get the model's root ancestor.
     *
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\RootAncestor
     */
    public function rootAncestor()
    {
        return $this->newRootAncestor(
            (new static())->newQuery(),
            $this,
            $this->getQualifiedParentKeyName(),
            $this->getLocalKeyName()
        );
    }

    /**
     * Instantiate a new RootAncestor relationship.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model $parent
     * @param string $foreignKey
     * @param string $localKey
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\RootAncestor
     */
    protected function newRootAncestor(Builder $query, Model $parent, $foreignKey, $localKey)
    {
        return new RootAncestor($query, $parent, $foreignKey, $localKey);
    }

    /**
     * Get the model's siblings.
     *
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Siblings
     */
    public function siblings()
    {
        return $this->newSiblings(
            (new static())->newQuery(),
            $this,
            $this->getQualifiedParentKeyName(),
            $this->getParentKeyName(),
            false
        );
    }

    /**
     * Get the model's siblings and itself.
     *
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Siblings
     */
    public function siblingsAndSelf()
    {
        return $this->newSiblings(
            (new static())->newQuery(),
            $this,
            $this->getQualifiedParentKeyName(),
            $this->getParentKeyName(),
            true
        );
    }

    /**
     * Instantiate a new Siblings relationship.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model $parent
     * @param string $foreignKey
     * @param string $localKey
     * @param bool $andSelf
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Siblings
     */
    protected function newSiblings(Builder $query, Model $parent, $foreignKey, $localKey, $andSelf)
    {
        return new Siblings($query, $parent, $foreignKey, $localKey, $andSelf);
    }

    /**
     * Get the first segment of the model's path.
     *
     * @return string
     */
    public function getFirstPathSegment()
    {
        $path = $this->attributes[$this->getPathName()];

        return explode($this->getPathSeparator(), $path)[0];
    }

    /**
     * Determine whether the model's path is nested.
     *
     * @return bool
     */
    public function hasNestedPath()
    {
        $path = $this->attributes[$this->getPathName()];

        return Str::contains($path, $this->getPathSeparator());
    }

    /**
     * Determine if an attribute is an integer.
     *
     * @param string $attribute
     * @return bool
     */
    public function isIntegerAttribute($attribute)
    {
        $casts = $this->getCasts();

        return isset($casts[$attribute]) && in_array($casts[$attribute], ['int', 'integer']);
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder($query);
    }

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param array $models
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Collection
     */
    public function newCollection(array $models = [])
    {
        return new Collection($models);
    }

    /**
     * Set an additional constraint for the recursive query.
     *
     * @param callable $constraint
     * @return void
     */
    public static function setRecursiveQueryConstraint(callable $constraint)
    {
        static::$recursiveQueryConstraint = $constraint;
    }

    /**
     * Unset the additional constraint for the recursive query.
     *
     * @return void
     */
    public static function unsetRecursiveQueryConstraint()
    {
        static::$recursiveQueryConstraint = null;
    }

    /**
     * Execute a query with an additional constraint for the recursive query.
     *
     * @param callable $constraint
     * @param callable $query
     * @return mixed
     */
    public static function withRecursiveQueryConstraint(callable $constraint, callable $query)
    {
        $previous = static::$recursiveQueryConstraint;

        static::$recursiveQueryConstraint = $constraint;

        $result = $query();

        static::$recursiveQueryConstraint = $previous;

        return $result;
    }
}

<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Graph\Collection;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Graph\Ancestors;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Graph\Descendants;

trait HasGraphAdjacencyList
{
    use HasGraphRelationshipScopes;
    use HasQueryConstraints;

    /**
     * Get the name of the pivot table.
     *
     * @return string
     */
    abstract public function getPivotTableName(): string;

    /**
     * Get the name of the parent key column.
     *
     * @return string
     */
    public function getParentKeyName(): string
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
        return $this->getPivotTableName() . '.' . $this->getParentKeyName();
    }

    /**
     * Get the name of the child key column.
     *
     * @return string
     */
    public function getChildKeyName(): string
    {
        return 'child_id';
    }

    /**
     * Get the qualified child key column.
     *
     * @return string
     */
    public function getQualifiedChildKeyName()
    {
        return $this->getPivotTableName() . '.' . $this->getChildKeyName();
    }

    /**
     * Get the name of the local key column.
     *
     * @return string
     */
    public function getLocalKeyName(): string
    {
        return $this->getKeyName();
    }

    /**
     * Get the qualified local key column.
     *
     * @return string
     */
    public function getQualifiedLocalKeyName(): string
    {
        return $this->qualifyColumn(
            $this->getLocalKeyName()
        );
    }

    /**
     * Get the name of the depth column.
     *
     * @return string
     */
    public function getDepthName(): string
    {
        return 'depth';
    }

    /**
     * Get the name of the path column.
     *
     * @return string
     */
    public function getPathName(): string
    {
        return 'path';
    }

    /**
     * Get the path separator.
     *
     * @return string
     */
    public function getPathSeparator(): string
    {
        return '.';
    }

    /**
     * Get the additional custom paths.
     *
     * @return array
     */
    public function getCustomPaths(): array
    {
        return [];
    }

    /**
     * Get the pivot table columns to retrieve.
     *
     * @return array
     */
    public function getPivotColumns(): array
    {
        return [];
    }

    /**
     * Get the name of the common table expression.
     *
     * @return string
     */
    public function getExpressionName(): string
    {
        return 'laravel_cte';
    }

    /**
     * Whether to enable cycle detection.
     *
     * @return bool
     */
    public function enableCycleDetection(): bool
    {
        return false;
    }

    /**
     * Whether to include the first row of the cycle in the query results.
     *
     * @return bool
     */
    public function includeCycleStart(): bool
    {
        return false;
    }

    /**
     * Get the name of the cycle detection column.
     *
     * @return string
     */
    public function getCycleDetectionColumnName(): string
    {
        return 'is_cycle';
    }

    /**
     * Get the model's ancestors.
     *
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Graph\Ancestors<static>
     */
    public function ancestors(): Ancestors
    {
        return $this->newAncestors(
            (new static())->newQuery(),
            $this,
            $this->getPivotTableName(),
            $this->getParentKeyName(),
            $this->getChildKeyName(),
            $this->getLocalKeyName(),
            $this->getLocalKeyName(),
            false
        );
    }

    /**
     * Get the model's ancestors and itself.
     *
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Graph\Ancestors<static>
     */
    public function ancestorsAndSelf(): Ancestors
    {
        return $this->newAncestors(
            (new static())->newQuery(),
            $this,
            $this->getPivotTableName(),
            $this->getParentKeyName(),
            $this->getChildKeyName(),
            $this->getLocalKeyName(),
            $this->getLocalKeyName(),
            true
        );
    }

    /**
     * Instantiate a new Ancestors relationship.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model $parent
     * @param string $table
     * @param string $foreignPivotKey
     * @param string $relatedPivotKey
     * @param string $parentKey
     * @param string $relatedKey
     * @param bool $andSelf
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Graph\Ancestors<static>
     */
    protected function newAncestors(
        Builder $query,
        Model $parent,
        string $table,
        string $foreignPivotKey,
        string $relatedPivotKey,
        string $parentKey,
        string $relatedKey,
        bool $andSelf
    ): Ancestors {
        return new Ancestors(
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
     * Get the model's children.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<static>
     */
    public function children(): BelongsToMany
    {
        return $this->belongsToMany(
            static::class,
            $this->getPivotTableName(),
            $this->getParentKeyName(),
            $this->getChildKeyName(),
            $this->getLocalKeyName(),
            $this->getLocalKeyName()
        )->withPivot(
            $this->getPivotColumns()
        );
    }

    /**
     * Get the model's children and itself.
     *
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Graph\Descendants<static>
     */
    public function childrenAndSelf(): Descendants
    {
        return $this->descendantsAndSelf()->whereDepth('<=', 1);
    }

    /**
     * Get the model's descendants.
     *
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Graph\Descendants<static>
     */
    public function descendants(): Descendants
    {
        return $this->newDescendants(
            (new static())->newQuery(),
            $this,
            $this->getPivotTableName(),
            $this->getParentKeyName(),
            $this->getChildKeyName(),
            $this->getLocalKeyName(),
            $this->getLocalKeyName(),
            false
        );
    }

    /**
     * Get the model's descendants and itself.
     *
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Graph\Descendants<static>
     */
    public function descendantsAndSelf(): Descendants
    {
        return $this->newDescendants(
            (new static())->newQuery(),
            $this,
            $this->getPivotTableName(),
            $this->getParentKeyName(),
            $this->getChildKeyName(),
            $this->getLocalKeyName(),
            $this->getLocalKeyName(),
            true
        );
    }

    /**
     * Instantiate a new Descendants relationship.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model $parent
     * @param string $table
     * @param string $foreignPivotKey
     * @param string $relatedPivotKey
     * @param string $parentKey
     * @param string $relatedKey
     * @param bool $andSelf
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Graph\Descendants<static>
     */
    protected function newDescendants(
        Builder $query,
        Model $parent,
        string $table,
        string $foreignPivotKey,
        string $relatedPivotKey,
        string $parentKey,
        string $relatedKey,
        bool $andSelf
    ): Descendants {
        return new Descendants(
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
     * Get the model's parents.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<static>
     */
    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(
            static::class,
            $this->getPivotTableName(),
            $this->getChildKeyName(),
            $this->getParentKeyName(),
            $this->getLocalKeyName(),
            $this->getLocalKeyName()
        )->withPivot(
            $this->getPivotColumns()
        );
    }

    /**
     * Get the model's parents and itself.
     *
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Graph\Ancestors<static>
     */
    public function parentsAndSelf(): Ancestors
    {
        return $this->ancestorsAndSelf()->whereDepth('>=', -1);
    }

    /**
     * Get the first segment of the model's path.
     *
     * @return string
     */
    public function getFirstPathSegment(): string
    {
        $path = $this->attributes[$this->getPathName()];

        return explode($this->getPathSeparator(), $path)[0];
    }

    /**
     * Determine if an attribute is an integer.
     *
     * @param string $attribute
     * @return bool
     */
    public function isIntegerAttribute(string $attribute): bool
    {
        $segments = explode('.', $attribute);
        $attribute = end($segments);

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
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Graph\Collection
     */
    public function newCollection(array $models = [])
    {
        return new Collection($models);
    }

    /**
     * Execute a query with a maximum depth constraint for the recursive query.
     *
     * @param int $maxDepth
     * @param callable $query
     * @return mixed
     */
    public static function withMaxDepth(int $maxDepth, callable $query): mixed
    {
        $operator = $maxDepth > 0 ? '<' : '>';

        return static::withRecursiveQueryConstraint(
            fn (Builder $query) => $query->whereDepth($operator, $maxDepth),
            $query
        );
    }
}

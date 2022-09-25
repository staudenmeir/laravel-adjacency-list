<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Staudenmeir\LaravelAdjacencyList\Query\Grammars\ExpressionGrammar;

trait HasRecursiveRelationshipScopes
{
    /**
     * Add a recursive expression for the whole tree to the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|null $maxDepth
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTree(Builder $query, $maxDepth = null)
    {
        $constraint = function (Builder $query) {
            $query->isRoot();
        };

        return $query->treeOf($constraint, $maxDepth);
    }

    /**
     * Add a recursive expression for a custom tree to the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param callable $constraint
     * @param int|null $maxDepth
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTreeOf(Builder $query, callable $constraint, $maxDepth = null)
    {
        return $query->withRelationshipExpression('desc', $constraint, 0, null, $maxDepth);
    }

    /**
     * Limit the query to models with children.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHasChildren(Builder $query)
    {
        $keys = (new static())->newQuery()
            ->select($this->getParentKeyName())
            ->hasParent();

        return $query->whereIn($this->getLocalKeyName(), $keys);
    }

    /**
     * Limit the query to models with a parent.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHasParent(Builder $query)
    {
        return $query->whereNotNull($this->getParentKeyName());
    }

    /**
     * Limit the query to leaf models.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIsLeaf(Builder $query)
    {
        $keys = (new static())->newQuery()
            ->select($this->getParentKeyName())
            ->hasParent();

        return $query->whereNotIn($this->getLocalKeyName(), $keys);
    }

    /**
     * Limit the query to root models.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIsRoot(Builder $query)
    {
        return $query->whereNull($this->getParentKeyName());
    }

    /**
     * Limit the query by depth.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $operator
     * @param mixed|null $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereDepth(Builder $query, $operator, $value = null)
    {
        $arguments = array_slice(func_get_args(), 1);

        return $query->where($this->getDepthName(), ...$arguments);
    }

    /**
     * Order the query breadth-first.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBreadthFirst(Builder $query)
    {
        return $query->orderBy($this->getDepthName());
    }

    /**
     * Order the query depth-first.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDepthFirst(Builder $query)
    {
        $sql = $query->getExpressionGrammar()->compileOrderByPath();

        return $query->orderByRaw($sql);
    }

    /**
     * Add a recursive expression for the relationship to the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $direction
     * @param callable $constraint
     * @param int $initialDepth
     * @param string|null $from
     * @param int|null $maxDepth
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithRelationshipExpression(Builder $query, $direction, callable $constraint, $initialDepth, $from = null, $maxDepth = null)
    {
        $from = $from ?: $this->getTable();

        $grammar = $query->getExpressionGrammar();

        $expression = $this->getInitialQuery($grammar, $constraint, $initialDepth, $from)
            ->unionAll(
                $this->getRecursiveQuery($grammar, $direction, $from, $maxDepth)
            );

        $name = $this->getExpressionName();

        $query->getModel()->setTable($name);

        return $query->withRecursiveExpression($name, $expression)->from($name);
    }

    /**
     * Get the initial query for a relationship expression.
     *
     * @param \Staudenmeir\LaravelAdjacencyList\Query\Grammars\ExpressionGrammar|\Illuminate\Database\Grammar $grammar
     * @param callable $constraint
     * @param int $initialDepth
     * @param string $from
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    protected function getInitialQuery(ExpressionGrammar $grammar, callable $constraint, $initialDepth, $from)
    {
        $depth = $grammar->wrap($this->getDepthName());

        $initialPath = $grammar->compileInitialPath(
            $this->getLocalKeyName(),
            $this->getPathName()
        );

        $query = $this->newModelQuery()
            ->select('*')
            ->selectRaw($initialDepth.' as '.$depth)
            ->selectRaw($initialPath)
            ->from($from);

        foreach ($this->getCustomPaths() as $path) {
            $query->selectRaw(
                $grammar->compileInitialPath($path['column'], $path['name'])
            );
        }

        $constraint($query);

        return $query;
    }

    /**
     * Get the recursive query for a relationship expression.
     *
     * @param \Staudenmeir\LaravelAdjacencyList\Query\Grammars\ExpressionGrammar|\Illuminate\Database\Grammar $grammar
     * @param string $direction
     * @param string $from
     * @param int|null $maxDepth
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    protected function getRecursiveQuery(ExpressionGrammar $grammar, $direction, $from, $maxDepth = null)
    {
        $name = $this->getExpressionName();

        $table = explode(' as ', $from)[1] ?? $from;

        $depth = $grammar->wrap($this->getDepthName());

        $joinColumns = [
            'asc' => [
                $name.'.'.$this->getParentKeyName(),
                $this->getQualifiedLocalKeyName(),
            ],
            'desc' => [
                $name.'.'.$this->getLocalKeyName(),
                $this->qualifyColumn($this->getParentKeyName()),
            ],
        ];

        if ($direction === 'both') {
            $recursiveDepth = "$depth + (case when {$joinColumns['desc'][1]}={$joinColumns['desc'][0]} then 1 else -1 end)";
        } else {
            $recursiveDepth = $depth.' '.($direction === 'asc' ? '-' : '+').' 1';
        }

        $recursivePath = $grammar->compileRecursivePath(
            $this->getQualifiedLocalKeyName(),
            $this->getPathName()
        );

        $recursivePathBindings = $grammar->getRecursivePathBindings($this->getPathSeparator());

        $query = $this->newModelQuery()
            ->select($table.'.*')
            ->selectRaw($recursiveDepth.' as '.$depth)
            ->selectRaw($recursivePath, $recursivePathBindings)
            ->from($from);

        foreach ($this->getCustomPaths() as $path) {
            $query->selectRaw(
                $grammar->compileRecursivePath(
                    $this->qualifyColumn($path['column']),
                    $path['name'],
                    $path['reverse'] ?? false,
                ),
                $grammar->getRecursivePathBindings($path['separator'])
            );
        }

        $this->addRecursiveQueryJoinsAndConstraints($query, $direction, $name, $joinColumns);

        if (!is_null($maxDepth)) {
            $query->where($this->getDepthName(), '<', $maxDepth);
        }

        return $query;
    }

    /**
     * Add join and where clauses to the recursive query for a relationship expression.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $direction
     * @param string $name
     * @param array $joinColumns
     * @return void
     */
    protected function addRecursiveQueryJoinsAndConstraints(Builder $query, $direction, $name, array $joinColumns)
    {
        if ($direction === 'both') {
            $query->join($name, function (JoinClause $join) use ($joinColumns) {
                $join->on($joinColumns['asc'][0], '=', $joinColumns['asc'][1])
                    ->orOn($joinColumns['desc'][0], '=', $joinColumns['desc'][1]);
            });

            $depth = $this->getDepthName();

            $query->where(function (Builder  $query) use ($depth, $joinColumns) {
                $query->where($depth, '=', 0)
                    ->orWhere(function (Builder $query) use ($depth, $joinColumns) {
                        $query->whereColumn($joinColumns['asc'][0], '=', $joinColumns['asc'][1])
                            ->where($depth, '<', 0);
                    })
                    ->orWhere(function (Builder $query) use ($depth, $joinColumns) {
                        $query->whereColumn($joinColumns['desc'][0], '=', $joinColumns['desc'][1])
                            ->where($depth, '>', 0);
                    });
            });
        } else {
            $query->join($name, $joinColumns[$direction][0], '=', $joinColumns[$direction][1]);
        }

        if (static::$recursiveQueryConstraint) {
            (static::$recursiveQueryConstraint)($query);
        }
    }
}

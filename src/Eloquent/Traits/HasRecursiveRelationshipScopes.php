<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\JoinClause;
use Staudenmeir\LaravelAdjacencyList\Query\Grammars\ExpressionGrammar;

trait HasRecursiveRelationshipScopes
{
    /**
     * Add a recursive expression for the whole tree to the query.
     *
     * @param \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static> $query
     * @param int|null $maxDepth
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>
     */
    public function scopeTree(Builder $query, $maxDepth = null)
    {
        $constraint = function (Builder $query) {
            $query->isRoot();
        };

        $query->treeOf($constraint, $maxDepth);

        return $query;
    }

    /**
     * Add a recursive expression for a custom tree to the query.
     *
     * @param \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static> $query
     * @param callable|\Illuminate\Database\Eloquent\Model $constraint
     * @param int|null $maxDepth
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>
     */
    public function scopeTreeOf(Builder $query, callable|Model $constraint, $maxDepth = null)
    {
        if ($constraint instanceof Model) {
            $constraint = fn ($query) => $query->whereKey($constraint->getKey());
        }

        $query->withRelationshipExpression('desc', $constraint, 0, null, $maxDepth);

        return $query;
    }

    /**
     * Limit the query to models with children.
     *
     * @param \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static> $query
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>
     */
    public function scopeHasChildren(Builder $query)
    {
        $keys = (new static())->newQuery()
            ->select($this->getParentKeyName())
            ->hasParent();

        $query->whereIn($this->getLocalKeyName(), $keys);

        return $query;
    }

    /**
     * Limit the query to models without children.
     *
     * @param \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static> $query
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>
     */
    public function scopeDoesntHaveChildren(Builder $query)
    {
        $query->isLeaf();

        return $query;
    }

    /**
     * Limit the query to models with a parent.
     *
     * @param \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static> $query
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>
     */
    public function scopeHasParent(Builder $query)
    {
        $query->whereNotNull($this->getParentKeyName());

        return $query;
    }

    /**
     * Limit the query to leaf models.
     *
     * @param \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static> $query
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>
     */
    public function scopeIsLeaf(Builder $query)
    {
        $keys = (new static())->newQuery()
            ->select($this->getParentKeyName())
            ->hasParent();

        $query->whereNotIn($this->getLocalKeyName(), $keys);

        return $query;
    }

    /**
     * Limit the query to root models.
     *
     * @param \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static> $query
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>
     */
    public function scopeIsRoot(Builder $query)
    {
        $query->whereNull($this->getParentKeyName());

        return $query;
    }

    /**
     * Limit the query by depth.
     *
     * @param \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static> $query
     * @param mixed $operator
     * @param mixed|null $value
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>
     */
    public function scopeWhereDepth(Builder $query, $operator, $value = null)
    {
        $arguments = array_slice(func_get_args(), 1);

        $query->where($this->getDepthName(), ...$arguments);

        return $query;
    }

    /**
     * Order the query breadth-first.
     *
     * @param \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static> $query
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>
     */
    public function scopeBreadthFirst(Builder $query)
    {
        $query->orderBy($this->getDepthName());

        return $query;
    }

    /**
     * Order the query depth-first.
     *
     * @param \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static> $query
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>
     */
    public function scopeDepthFirst(Builder $query)
    {
        $sql = $query->getExpressionGrammar()->compileOrderByPath();

        $query->orderByRaw($sql);

        return $query;
    }

    /**
     * Add a recursive expression for the relationship to the query.
     *
     * @param \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static> $query
     * @param string $direction
     * @param callable $constraint
     * @param int $initialDepth
     * @param string|null $from
     * @param int|null $maxDepth
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>
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

        $query->getQuery()->withRecursiveExpression($name, $expression->getQuery())->from($name);

        return $query;
    }

    /**
     * Get the initial query for a relationship expression.
     *
     * @param \Staudenmeir\LaravelAdjacencyList\Query\Grammars\ExpressionGrammar $grammar
     * @param callable $constraint
     * @param int $initialDepth
     * @param string $from
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>
     */
    protected function getInitialQuery(ExpressionGrammar $grammar, callable $constraint, $initialDepth, $from)
    {
        $table = explode(' as ', $from)[1] ?? $from;

        $depth = $grammar->wrap($this->getDepthName());

        $initialPath = $grammar->compileInitialPath(
            $this->getLocalKeyName(),
            $this->getPathName()
        );

        /** @var \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static> $query */
        $query = $this->newModelQuery()
            ->select("$table.*")
            ->selectRaw($initialDepth.' as '.$depth)
            ->selectRaw($initialPath)
            ->from($from);

        foreach ($this->getCustomPaths() as $path) {
            $query->selectRaw(
                $grammar->compileInitialPath($path['column'], $path['name'])
            );
        }

        $constraint($query);

        if (static::$initialQueryConstraint) {
            (static::$initialQueryConstraint)($query);
        }

        return $query;
    }

    /**
     * Get the recursive query for a relationship expression.
     *
     * @param \Staudenmeir\LaravelAdjacencyList\Query\Grammars\ExpressionGrammar $grammar
     * @param string $direction
     * @param string $from
     * @param int|null $maxDepth
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>
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
            $left = $grammar->wrap($joinColumns['desc'][1]);
            $right = $grammar->wrap($joinColumns['desc'][0]);

            $recursiveDepth = "$depth + (case when $left=$right then 1 else -1 end)";
        } else {
            $recursiveDepth = $depth.' '.($direction === 'asc' ? '-' : '+').' 1';
        }

        $recursivePath = $grammar->compileRecursivePath(
            $this->getQualifiedLocalKeyName(),
            $this->getPathName()
        );

        $recursivePathBindings = $grammar->getRecursivePathBindings($this->getPathSeparator());

        /** @var \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static> $query */
        $query = $this->newModelQuery()
            ->select($table.'.*')
            ->selectRaw($recursiveDepth.' as '.$depth)
            ->selectRaw($recursivePath, $recursivePathBindings)
            ->from($from);

        foreach ($this->getCustomPaths() as $path) {
            $query->selectRaw(
                $grammar->compileRecursivePath(
                    is_string($path['column']) ? $this->qualifyColumn($path['column']) : $path['column'],
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
     * @param \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static> $query
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

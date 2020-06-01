<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use RuntimeException;
use Staudenmeir\LaravelAdjacencyList\Query\Grammars\ExpressionGrammar;
use Staudenmeir\LaravelAdjacencyList\Query\Grammars\MySqlGrammar;
use Staudenmeir\LaravelAdjacencyList\Query\Grammars\PostgresGrammar;
use Staudenmeir\LaravelAdjacencyList\Query\Grammars\SQLiteGrammar;
use Staudenmeir\LaravelAdjacencyList\Query\Grammars\SqlServerGrammar;

trait HasRecursiveRelationshipScopes
{
    /**
     * Add a recursive expression for the relationship's whole tree to the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTree(Builder $query)
    {
        $constraint = function (Builder $query) {
            $query->isRoot();
        };

        return $query->withRelationshipExpression('desc', $constraint, 0);
    }

    /**
     * Limit the query to models with children.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHasChildren(Builder $query)
    {
        $keys = (new static)->newQuery()
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
        $keys = (new static)->newQuery()
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
     * @param mixed $value
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
        return $query->orderBy($this->getPathName());
    }

    /**
     * Add a recursive expression for the relationship to the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $direction
     * @param callable $constraint
     * @param int $initialDepth
     * @param string|null $from
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithRelationshipExpression(Builder $query, $direction, callable $constraint, $initialDepth, $from = null)
    {
        $from = $from ?: $this->getTable();

        $grammar = $this->getExpressionGrammar($query);

        $expression = $this->getInitialQuery($grammar, $constraint, $initialDepth, $from)
            ->unionAll(
                $this->getRecursiveQuery($grammar, $direction, $from)
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

        $constraint($query);

        return $query;
    }

    /**
     * Get the recursive query for a relationship expression.
     *
     * @param \Staudenmeir\LaravelAdjacencyList\Query\Grammars\ExpressionGrammar|\Illuminate\Database\Grammar $grammar
     * @param string $direction
     * @param string $from
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    protected function getRecursiveQuery(ExpressionGrammar $grammar, $direction, $from)
    {
        $name = $this->getExpressionName();

        $table = explode(' as ', $from)[1] ?? $from;

        $depth = $grammar->wrap($this->getDepthName());

        $recursiveDepth = $grammar->wrap($this->getDepthName()).' '.($direction === 'asc' ? '-' : '+').' 1';

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

        if ($direction === 'asc') {
            $first = $this->getParentKeyName();
            $second = $this->getQualifiedLocalKeyName();
        } else {
            $first = $this->getLocalKeyName();
            $second = $this->qualifyColumn($this->getParentKeyName());
        }

        $query->join($name, $name.'.'.$first, '=', $second);

        return $query;
    }

    /**
     * Get the expression grammar.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Staudenmeir\LaravelAdjacencyList\Query\Grammars\ExpressionGrammar
     */
    protected function getExpressionGrammar(Builder $query)
    {
        $driver = $query->getConnection()->getDriverName();

        switch ($driver) {
            case 'mysql':
                return $query->getConnection()->withTablePrefix(new MySqlGrammar);
            case 'pgsql':
                return $query->getConnection()->withTablePrefix(new PostgresGrammar);
            case 'sqlite':
                return $query->getConnection()->withTablePrefix(new SQLiteGrammar);
            case 'sqlsrv':
                return $query->getConnection()->withTablePrefix(new SqlServerGrammar);
        }

        throw new RuntimeException('This database is not supported.'); // @codeCoverageIgnore
    }
}

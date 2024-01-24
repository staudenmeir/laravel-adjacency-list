<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Traits;

use Illuminate\Database\Eloquent\Builder;
use RuntimeException;
use Staudenmeir\LaravelAdjacencyList\Query\Grammars\ExpressionGrammar;

trait HasGraphRelationshipScopes
{
    /**
     * Add a recursive expression for a custom subgraph to the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param callable $constraint
     * @param int|null $maxDepth
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSubgraph(Builder $query, callable $constraint, int $maxDepth = null): Builder
    {
        return $query->withRelationshipExpression('desc', $constraint, 0, null, $maxDepth);
    }

    /**
     * Limit the query by depth.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $operator
     * @param mixed|null $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereDepth(Builder $query, mixed $operator, mixed $value = null): Builder
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
    public function scopeBreadthFirst(Builder $query): Builder
    {
        return $query->orderBy($this->getDepthName());
    }

    /**
     * Order the query depth-first.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDepthFirst(Builder $query): Builder
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
     * @param string $union
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithRelationshipExpression(
        Builder $query,
        string $direction,
        callable $constraint,
        int $initialDepth,
        string $from = null,
        int $maxDepth = null,
        string $union = 'unionAll'
    ): Builder {
        $from = $from ?: $this->getTable();

        $grammar = $query->getExpressionGrammar();

        $expression = $this->getInitialQuery($grammar, $constraint, $initialDepth, $from)
            ->$union(
                $this->getRecursiveQuery($grammar, $direction, $from, $maxDepth)
            );

        $name = $this->getExpressionName();

        $query->getModel()->setTable($name);

        return $query->withRecursiveExpression($name, $expression)->from($name);
    }

    /**
     * Get the initial query for a relationship expression.
     *
     * @param \Staudenmeir\LaravelAdjacencyList\Query\Grammars\ExpressionGrammar $grammar
     * @param callable $constraint
     * @param int $initialDepth
     * @param array $pivotColumns
     * @param string $from
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    protected function getInitialQuery(
        ExpressionGrammar $grammar,
        callable $constraint,
        int $initialDepth,
        string $from
    ): Builder {
        $table = explode(' as ', $from)[1] ?? $from;

        $pivotTable = $this->getPivotTableName();

        $depth = $grammar->wrap(
            $this->getDepthName()
        );

        $initialPath = $grammar->compileInitialPath(
            $this->getQualifiedLocalKeyName(),
            $this->getPathName()
        );

        $query = $this->newModelQuery()
            ->select("$table.*")
            ->selectRaw($initialDepth . ' as ' . $depth)
            ->selectRaw($initialPath)
            ->from($from);

        $this->addInitialQueryCustomPaths($query, $grammar);

        $this->addInitialQueryPivotColumns($query, $grammar, $pivotTable, $initialDepth);

        $this->addInitialQueryCycleDetection($query, $grammar);

        $this->addInitialQueryJoins($query, $pivotTable, $initialDepth);

        $constraint($query);

        if (static::$initialQueryConstraint) {
            (static::$initialQueryConstraint)($query);
        }

        return $query;
    }

    /**
     * Add custom paths to the initial query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Staudenmeir\LaravelAdjacencyList\Query\Grammars\ExpressionGrammar $grammar
     * @return void
     */
    protected function addInitialQueryCustomPaths(Builder $query, ExpressionGrammar $grammar): void
    {
        foreach ($this->getCustomPaths() as $path) {
            $query->selectRaw(
                $grammar->compileInitialPath(
                    is_string($path['column']) ? $this->qualifyColumn($path['column']) : $path['column'],
                    $path['name']
                )
            );
        }
    }

    /**
     * Add pivot columns to the initial query for a relationship expression.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Staudenmeir\LaravelAdjacencyList\Query\Grammars\ExpressionGrammar $grammar
     * @param string $pivotTable
     * @param int $initialDepth
     * @return void
     */
    protected function addInitialQueryPivotColumns(
        Builder $query,
        ExpressionGrammar $grammar,
        string $pivotTable,
        int $initialDepth
    ): void {
        $columns = [$this->getParentKeyName(), $this->getChildKeyName(), ...$this->getPivotColumns()];

        if ($initialDepth === 0) {
            if (!$query->getConnection()->isDoctrineAvailable()) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException(
                    'This feature requires the doctrine/dbal package. Please run "composer require doctrine/dbal".'
                );
                // @codeCoverageIgnoreEnd
            }

            $localKeyType = $query->getConnection()->getSchemaBuilder()->getColumnType(
                (new $this())->getTable(),
                $this->getLocalKeyName()
            );

            foreach ($columns as $i => $column) {
                if ($i < 2) {
                    $type = $localKeyType;
                } else {
                    $type = $query->getConnection()->getSchemaBuilder()->getColumnType($pivotTable, $column);
                }

                $doctrineColumn = $query->getConnection()->getDoctrineColumn($pivotTable, $column);

                $null = $grammar->compilePivotColumnNullValue(
                    $type,
                    $doctrineColumn->getPrecision(),
                    $doctrineColumn->getScale()
                );

                $query->selectRaw("$null as " . $grammar->wrap("pivot_$column"));
            }
        } else {
            foreach ($columns as $column) {
                $query->addSelect("$pivotTable.$column as pivot_$column");
            }
        }
    }

    /**
     * Add cycle detection to the initial query for a relationship expression.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Staudenmeir\LaravelAdjacencyList\Query\Grammars\ExpressionGrammar $grammar
     * @return void
     */
    protected function addInitialQueryCycleDetection(Builder $query, ExpressionGrammar $grammar): void
    {
        if ($this->enableCycleDetection() && $this->includeCycleStart()) {
            $query->selectRaw(
                $grammar->compileCycleDetectionInitialSelect(
                    $this->getCycleDetectionColumnName()
                )
            );
        }
    }

    /**
     * Add join clauses to the initial query for a relationship expression.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $pivotTable
     * @param int $initialDepth
     * @return void
     */
    protected function addInitialQueryJoins(Builder $query, string $pivotTable, int $initialDepth): void
    {
        if ($initialDepth < 0) {
            $query->join(
                $pivotTable,
                $this->getQualifiedLocalKeyName(),
                '=',
                $this->getQualifiedParentKeyName()
            );
        } elseif ($initialDepth > 0) {
            $query->join(
                $pivotTable,
                $this->getQualifiedLocalKeyName(),
                '=',
                $this->getQualifiedChildKeyName()
            );
        }
    }

    /**
     * Get the recursive query for a relationship expression.
     *
     * @param \Staudenmeir\LaravelAdjacencyList\Query\Grammars\ExpressionGrammar $grammar
     * @param string $direction
     * @param string $from
     * @param int|null $maxDepth
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    protected function getRecursiveQuery(
        ExpressionGrammar $grammar,
        string $direction,
        string $from,
        int $maxDepth = null
    ): Builder {
        $name = $this->getExpressionName();

        $table = explode(' as ', $from)[1] ?? $from;

        $pivotTable = $this->getPivotTableName();

        $depth = $grammar->wrap($this->getDepthName());

        $joinColumns = [
            'asc' => [
                $name . '.' . $this->getLocalKeyName(),
                $this->getQualifiedChildKeyName(),
            ],
            'desc' => [
                $name . '.' . $this->getLocalKeyName(),
                $this->getQualifiedParentKeyName(),
            ],
        ];

        $recursiveDepth = $depth . ' ' . ($direction === 'asc' ? '-' : '+') . ' 1';

        $recursivePath = $grammar->compileRecursivePath(
            $this->getQualifiedLocalKeyName(),
            $this->getPathName()
        );

        $recursivePathBindings = $grammar->getRecursivePathBindings($this->getPathSeparator());

        $query = $this->newModelQuery()
            ->select($table . '.*')
            ->selectRaw($recursiveDepth . ' as ' . $depth)
            ->selectRaw($recursivePath, $recursivePathBindings)
            ->from($from);

        $this->addRecursiveQueryCustomPaths($query, $grammar);

        $this->addRecursiveQueryPivotColumns($query, $pivotTable);

        $this->addRecursiveQueryCycleDetection($query, $grammar);

        $this->addRecursiveQueryJoinsAndConstraints($query, $pivotTable, $direction, $name, $joinColumns);

        if (!is_null($maxDepth)) {
            $query->where($this->getDepthName(), '<', $maxDepth);
        }

        return $query;
    }

    /**
     * Add customs path to the recursive query for a relationship expression.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Staudenmeir\LaravelAdjacencyList\Query\Grammars\ExpressionGrammar $grammar
     * @return void
     */
    protected function addRecursiveQueryCustomPaths(Builder $query, ExpressionGrammar $grammar): void
    {
        foreach ($this->getCustomPaths() as $path) {
            $query->selectRaw(
                $grammar->compileRecursivePath(
                    is_string($path['column']) ? $this->qualifyColumn($path['column']) : $path['column'],
                    $path['name'],
                    $path['reverse'] ?? false
                ),
                $grammar->getRecursivePathBindings($path['separator'])
            );
        }
    }

    /**
     * Add pivot columns to the recursive query for a relationship expression.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $pivotTable
     * @return void
     */
    protected function addRecursiveQueryPivotColumns(Builder $query, string $pivotTable): void
    {
        $columns = [$this->getParentKeyName(), $this->getChildKeyName(), ...$this->getPivotColumns()];

        foreach ($columns as $column) {
            $query->addSelect("$pivotTable.$column as pivot_$column");
        }
    }

    /**
     * Add cycle detection to the recursive query for a relationship expression.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Staudenmeir\LaravelAdjacencyList\Query\Grammars\ExpressionGrammar $grammar
     * @return void
     */
    protected function addRecursiveQueryCycleDetection(Builder $query, ExpressionGrammar $grammar): void
    {
        if (!$this->enableCycleDetection()) {
            return;
        }

        $sql = $grammar->compileCycleDetection(
            $this->getQualifiedLocalKeyName(),
            $this->getPathName()
        );

        $bindings = $grammar->getCycleDetectionBindings(
            $this->getPathSeparator()
        );

        if ($this->includeCycleStart()) {
            $cycleDetectionColumn = $this->getCycleDetectionColumnName();

            $query->selectRaw(
                $grammar->compileCycleDetectionRecursiveSelect($sql, $cycleDetectionColumn),
                $bindings
            );

            $query->whereRaw(
                $grammar->compileCycleDetectionStopConstraint($cycleDetectionColumn)
            );
        } else {
            $query->whereRaw("not($sql)", $bindings);
        }
    }

    /**
     * Add join and where clauses to the recursive query for a relationship expression.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $pivotTable
     * @param string $direction
     * @param string $name
     * @param array $joinColumns
     * @return void
     */
    protected function addRecursiveQueryJoinsAndConstraints(
        Builder $query,
        string $pivotTable,
        string $direction,
        string $name,
        array $joinColumns
    ): void {
        if ($direction === 'desc') {
            $query->join(
                $pivotTable,
                $this->getQualifiedLocalKeyName(),
                '=',
                $this->getQualifiedChildKeyName()
            );
        } else {
            $query->join(
                $pivotTable,
                $this->getQualifiedLocalKeyName(),
                '=',
                $this->getQualifiedParentKeyName()
            );
        }

        $query->join($name, $joinColumns[$direction][0], '=', $joinColumns[$direction][1]);

        if (static::$recursiveQueryConstraint) {
            (static::$recursiveQueryConstraint)($query);
        }
    }
}

<?php

namespace Staudenmeir\LaravelAdjacencyList\Query\Grammars;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\SqlServerGrammar as Base;
use RuntimeException;

class SqlServerGrammar extends Base implements ExpressionGrammar
{
    use OrdersByPath;

    /**
     * Compile an initial path.
     *
     * @param string $column
     * @param string $alias
     * @return string
     */
    public function compileInitialPath($column, $alias)
    {
        return 'cast('.$this->wrap($column).' as varchar) as '.$this->wrap($alias);
    }

    /**
     * Compile a recursive path.
     *
     * @param string $column
     * @param string $alias
     * @param bool $reverse
     * @return string
     */
    public function compileRecursivePath($column, $alias, bool $reverse = false)
    {
        $wrappedColumn = $this->wrap($column);
        $wrappedAlias = $this->wrap($alias);

        if ($reverse) {
            return "cast(cast($wrappedColumn as varchar) + ? + $wrappedAlias as varchar) as $wrappedAlias";
        }

        return "cast($wrappedAlias + ? + cast($wrappedColumn as varchar) as varchar) as $wrappedAlias";
    }

    /**
     * Get the recursive path bindings.
     *
     * @param string $separator
     * @return array
     */
    public function getRecursivePathBindings($separator)
    {
        return [$separator];
    }

    /**
     * Select a concatenated list of paths.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param string $expression
     * @param string $column
     * @param string $pathSeparator
     * @param string $listSeparator
     * @return \Illuminate\Database\Query\Builder
     */
    public function selectPathList(Builder $query, $expression, $column, $pathSeparator, $listSeparator)
    {
        return $query->selectRaw(
            'stuff((select ? + '.$this->wrap($column).' from '.$this->wrapTable($expression)." for xml path('')), 1, ?, '')",
            [$listSeparator, strlen($listSeparator)]
        );
    }

    /**
     * Compile a pivot column null value.
     *
     * @param string $type
     * @return string
     */
    public function compilePivotColumnNullValue(string $type): string
    {
        throw new RuntimeException('This graph relationship feature is not supported on SQL Server.'); // @codeCoverageIgnore
    }

    /**
     * Compile a cycle detection clause.
     *
     * @param string $localKey
     * @param string $path
     * @return string
     */
    public function compileCycleDetection(string $localKey, string $path): string
    {
        $localKey = $this->wrap($localKey);
        $path = $this->wrap($path);

        $castLocalKey = "cast($localKey as varchar)";

        return "charindex($castLocalKey + ?, $path) > 0 or charindex(? + $castLocalKey + ?, $path) > 0";
    }

    /**
     * Get the cycle detection bindings.
     *
     * @param string $pathSeparator
     * @return array
     */
    public function getCycleDetectionBindings(string $pathSeparator): array
    {
        return [$pathSeparator, $pathSeparator, $pathSeparator];
    }

    /**
     * Compile the initial select expression for a cycle detection clause.
     *
     * @param string $column
     * @return string
     */
    public function compileCycleDetectionInitialSelect(string $column): string
    {
        return '0 as ' . $this->wrap($column);
    }

    /**
     * Compile the recursive select expression for a cycle detection clause.
     *
     * @param string $sql
     * @param string $column
     * @return string
     */
    public function compileCycleDetectionRecursiveSelect(string $sql, string $column): string
    {
        return "case when $sql then 1 else 0 end as " . $this->wrap($column);
    }

    /**
     * Compile the stop constraint for a cycle detection clause.
     *
     * @param string $column
     * @return string
     */
    public function compileCycleDetectionStopConstraint(string $column): string
    {
        return $this->wrap($column) . ' = 0';
    }

    /**
     * Determine whether the database supports the UNION operator in a recursive expression.
     *
     * @return bool
     */
    public function supportsUnionInRecursiveExpression(): bool
    {
        return false;
    }
}

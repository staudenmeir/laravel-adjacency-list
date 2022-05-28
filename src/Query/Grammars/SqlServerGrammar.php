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
     * @return string
     */
    public function compileRecursivePath($column, $alias)
    {
        return 'cast('.$this->wrap($alias).' + ? + cast('.$this->wrap($column).' as varchar) as varchar) as '.$this->wrap($alias);
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
        throw new RuntimeException('Graph relationships are not supported on SQL Server.'); // @codeCoverageIgnore
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
        throw new RuntimeException('Graph relationships are not supported on SQL Server.'); // @codeCoverageIgnore
    }

    /**
     * Get the cycle detection bindings.
     *
     * @param string $pathSeparator
     * @return array
     */
    public function getCycleDetectionBindings(string $pathSeparator): array
    {
        throw new RuntimeException('Graph relationships are not supported on SQL Server.'); // @codeCoverageIgnore
    }
}

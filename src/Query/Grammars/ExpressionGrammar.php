<?php

namespace Staudenmeir\LaravelAdjacencyList\Query\Grammars;

use Illuminate\Database\Query\Builder;

interface ExpressionGrammar
{
    /**
     * Compile an initial path.
     *
     * @param string $column
     * @param string $alias
     * @return string
     */
    public function compileInitialPath($column, $alias);

    /**
     * Compile a recursive path.
     *
     * @param string $column
     * @param string $alias
     * @param bool $reverse
     * @return string
     */
    public function compileRecursivePath($column, $alias, bool $reverse = false);

    /**
     * Get the recursive path bindings.
     *
     * @param string $separator
     * @return array
     */
    public function getRecursivePathBindings($separator);

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
    public function selectPathList(Builder $query, $expression, $column, $pathSeparator, $listSeparator);

    /**
     * Compile an "order by path" clause.
     *
     * @return string
     */
    public function compileOrderByPath();

    /**
     * Compile a pivot column null value.
     *
     * @param string $type
     * @param int $precision
     * @param int $scale
     * @return string
     */
    public function compilePivotColumnNullValue(string $type, int $precision, int $scale): string;

    /**
     * Compile a cycle detection clause.
     *
     * @param string $localKey
     * @param string $path
     * @return string
     */
    public function compileCycleDetection(string $localKey, string $path): string;

    /**
     * Get the cycle detection bindings.
     *
     * @param string $pathSeparator
     * @return array
     */
    public function getCycleDetectionBindings(string $pathSeparator): array;

    /**
     * Compile the initial select expression for a cycle detection clause.
     *
     * @param string $column
     * @return string
     */
    public function compileCycleDetectionInitialSelect(string $column): string;

    /**
     * Compile the recursive select expression for a cycle detection clause.
     *
     * @param string $sql
     * @param string $column
     * @return string
     */
    public function compileCycleDetectionRecursiveSelect(string $sql, string $column): string;

    /**
     * Compile the stop constraint for a cycle detection clause.
     *
     * @param string $column
     * @return string
     */
    public function compileCycleDetectionStopConstraint(string $column): string;

    /**
     * Determine whether the database supports the UNION operator in a recursive expression.
     *
     * @return bool
     */
    public function supportsUnionInRecursiveExpression(): bool;
}

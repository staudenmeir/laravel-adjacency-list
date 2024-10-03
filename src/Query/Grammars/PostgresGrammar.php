<?php

namespace Staudenmeir\LaravelAdjacencyList\Query\Grammars;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\PostgresGrammar as Base;

class PostgresGrammar extends Base implements ExpressionGrammar
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
        if (is_string($column) && $this->model->isIntegerAttribute($column)) {
            return 'array['.$this->wrap($column).'] as '.$this->wrap($alias);
        }

        return 'array[('.$this->wrap($column)." || '')::varchar] as ".$this->wrap($alias);
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

        if (is_string($column) && !$this->model->isIntegerAttribute($column)) {
            $wrappedColumn .= '::varchar';
        }

        return $reverse ? "$wrappedColumn || $wrappedAlias" : "$wrappedAlias || $wrappedColumn";
    }

    /** @inheritDoc */
    public function getRecursivePathBindings($separator)
    {
        return [];
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
            'string_agg(array_to_string('.$this->wrap($column).', ?), ?)',
            [$pathSeparator, $listSeparator]
        )->from($expression);
    }

    /**
     * Compile a pivot column null value.
     *
     * @param string $typeName
     * @param string $type
     * @return string
     */
    public function compilePivotColumnNullValue(string $typeName, string $type): string
    {
        $cast = match ($typeName) {
            'datetime' => 'timestamp',
            'string' => 'varchar',
            default => $typeName,
        };

        return "null::$cast";
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
        $wrappedLocalKey = $this->wrap($localKey);
        $wrappedPath = $this->wrap($path);

        if ($this->model->isIntegerAttribute($localKey)) {
            return "$wrappedLocalKey = any($wrappedPath)";
        }

        return "$wrappedLocalKey::varchar = any($wrappedPath)";
    }

    /** @inheritDoc */
    public function getCycleDetectionBindings(string $pathSeparator): array
    {
        return [];
    }

    /**
     * Compile the initial select expression for a cycle detection clause.
     *
     * @param string $column
     * @return string
     */
    public function compileCycleDetectionInitialSelect(string $column): string
    {
        return 'false as ' . $this->wrap($column);
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
        return $sql;
    }

    /**
     * Compile the stop constraint for a cycle detection clause.
     *
     * @param string $column
     * @return string
     */
    public function compileCycleDetectionStopConstraint(string $column): string
    {
        return 'not ' . $this->wrap($column);
    }

    /**
     * Determine whether the database supports the UNION operator in a recursive expression.
     *
     * @return bool
     */
    public function supportsUnionInRecursiveExpression(): bool
    {
        return true;
    }
}

<?php

namespace Staudenmeir\LaravelAdjacencyList\Query\Grammars\Traits;

use Illuminate\Database\Query\Builder;
use Staudenmeir\LaravelAdjacencyList\Query\Grammars\OrdersByPath;

trait CompilesMySqlAdjacencyLists
{
    use OrdersByPath;

    /** @inheritDoc */
    public function compileInitialPath($column, $alias)
    {
        return 'cast(' . $this->wrap($column) . ' as char(65535)) as ' . $this->wrap($alias);
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

        return $reverse ? "concat($wrappedColumn, ?, $wrappedAlias)" : "concat($wrappedAlias, ?, $wrappedColumn)";
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
            'group_concat(' . $this->wrap($column) . " separator '$listSeparator')"
        )->from($expression);
    }

    /**
     * Compile an "order by path" clause.
     *
     * @return string
     */
    public function compileOrderByPath()
    {
        $column = $this->model->getLocalKeyName();

        $path = $this->wrap(
            $this->model->getPathName()
        );

        $pathSeparator = $this->model->getPathSeparator();

        if (!$this->model->isIntegerAttribute($column)) {
            return "$path asc";
        }

        return <<<SQL
regexp_replace(
    regexp_replace(
        $path,
        '(^|[$pathSeparator])(\\\\d+)',
        '$100000000000000000000$2'
    ),
    '0+(\\\\d\{20\})([$pathSeparator]|$)',
    '$1$2'
) asc
SQL;
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
        if ($typeName === 'decimal') {
            preg_match('/\((\d+),(\d+)\)/', $type, $matches);

            [$precision, $scale] = [$matches[1], $matches[2]];
        } else {
            [$precision, $scale] = [null, null];
        }

        $cast = match ($typeName) {
            'bigint', 'boolean', 'integer', 'smallint', 'tinyint' => 'signed',
            'decimal' => "decimal($precision, $scale)",
            'timestamp' => 'datetime',
            'varchar' => 'char(65535)',
            default => $type,
        };

        return "cast(null as $cast)";
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

        return "instr($path, concat($localKey, ?)) = 1 || instr($path, concat(?, $localKey, ?)) > 1";
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

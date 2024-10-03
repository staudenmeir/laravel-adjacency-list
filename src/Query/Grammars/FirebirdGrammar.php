<?php

namespace Staudenmeir\LaravelAdjacencyList\Query\Grammars;

use HarryGulliford\Firebird\Query\Grammars\FirebirdGrammar as Base;
use Illuminate\Database\Query\Builder;
use RuntimeException;

class FirebirdGrammar extends Base implements ExpressionGrammar
{
    use OrdersByPath;

    public function compileInitialPath($column, $alias)
    {
        return 'cast(' . $this->wrap($column) . ' as varchar(8191)) as ' . $this->wrap($alias);
    }

    public function compileRecursivePath($column, $alias, bool $reverse = false)
    {
        $wrappedColumn = $this->wrap($column);
        $wrappedAlias = $this->wrap($alias);
        $placeholder = 'cast(? as varchar(8191))';

        return $reverse ? "($wrappedColumn || $placeholder || $wrappedAlias)" : "($wrappedAlias || $placeholder || $wrappedColumn)";
    }

    /** @inheritDoc */
    public function getRecursivePathBindings($separator)
    {
        return [$separator];
    }

    public function selectPathList(Builder $query, $expression, $column, $pathSeparator, $listSeparator)
    {
        return $query->selectRaw(
            'list(' . $this->wrap($column) . ", '$listSeparator')"
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
        throw new RuntimeException('This graph relationship feature is not supported on Firebird.'); // @codeCoverageIgnore
    }

    public function compileCycleDetection(string $localKey, string $path): string
    {
        $localKey = $this->wrap($localKey);
        $path = $this->wrap($path);

        return "position($localKey || ?, $path) > 0 or position(? || $localKey || ?, $path) > 0";
    }

    /** @inheritDoc */
    public function getCycleDetectionBindings(string $pathSeparator): array
    {
        return [$pathSeparator, $pathSeparator, $pathSeparator];
    }

    public function compileCycleDetectionInitialSelect(string $column): string
    {
        return 'false as ' . $this->wrap($column);
    }

    public function compileCycleDetectionRecursiveSelect(string $sql, string $column): string
    {
        return $sql;
    }

    public function compileCycleDetectionStopConstraint(string $column): string
    {
        return 'not ' . $this->wrap($column);
    }

    public function supportsUnionInRecursiveExpression(): bool
    {
        return false;
    }
}

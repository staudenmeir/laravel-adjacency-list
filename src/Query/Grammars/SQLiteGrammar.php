<?php

namespace Staudenmeir\LaravelAdjacencyList\Query\Grammars;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\SQLiteGrammar as Base;

class SQLiteGrammar extends Base implements ExpressionGrammar
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
        return 'cast('.$this->wrap($column).' as text) as '.$this->wrap($alias);
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
        return $this->wrap($alias).' || ? || '.$this->wrap($column);
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
            'group_concat('.$this->wrap($column).', ?)',
            [$listSeparator]
        )->from($expression);
    }
}

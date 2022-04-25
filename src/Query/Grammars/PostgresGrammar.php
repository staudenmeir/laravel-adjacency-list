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
        if ($this->model->isIntegerAttribute($column)) {
            return 'array['.$this->wrap($column).'] as '.$this->wrap($alias);
        }

        return 'array[('.$this->wrap($column)." || '')::varchar] as ".$this->wrap($alias);
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
        $segments = explode('.', $column);
        $attribute = end($segments);

        if ($this->model->isIntegerAttribute($attribute)) {
            return $this->wrap($alias).' || '.$this->wrap($column);
        }

        return $this->wrap($alias) . ' || ' . $this->wrap($column) . '::varchar';
    }

    /**
     * Get the recursive path bindings.
     *
     * @param string $separator
     * @return array
     */
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
}

<?php

namespace Staudenmeir\LaravelAdjacencyList\Query\Grammars;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\MySqlGrammar as Base;

class MySqlGrammar extends Base implements ExpressionGrammar
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
        return 'cast(' . $this->wrap($column) . ' as char(65535)) as ' . $this->wrap($alias);
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
        return 'concat(' . $this->wrap($alias) . ', ?, ' . $this->wrap($column) . ')';
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
}

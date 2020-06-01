<?php

namespace Staudenmeir\LaravelAdjacencyList\Query\Grammars;

use Illuminate\Database\Query\Grammars\SqlServerGrammar as Base;

class SqlServerGrammar extends Base implements ExpressionGrammar
{
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
}

<?php

namespace Staudenmeir\LaravelAdjacencyList\Query\Grammars;

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
     * @param string $separator
     * @return string
     */
    public function compileRecursivePath($column, $alias, $separator);
}

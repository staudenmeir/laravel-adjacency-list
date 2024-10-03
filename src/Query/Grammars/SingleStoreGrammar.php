<?php

namespace Staudenmeir\LaravelAdjacencyList\Query\Grammars;

use SingleStore\Laravel\Query\Grammar;
use Staudenmeir\LaravelAdjacencyList\Query\Grammars\Traits\CompilesMySqlAdjacencyLists;

class SingleStoreGrammar extends Grammar implements ExpressionGrammar
{
    use CompilesMySqlAdjacencyLists;

    /** @inheritDoc */
    public function compileInitialPath($column, $alias)
    {
        return 'cast(' . $this->wrap($column) . ' as char(8192)) as ' . $this->wrap($alias);
    }
}

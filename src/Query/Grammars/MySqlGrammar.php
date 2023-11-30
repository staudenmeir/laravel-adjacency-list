<?php

namespace Staudenmeir\LaravelAdjacencyList\Query\Grammars;

use Illuminate\Database\Query\Grammars\MySqlGrammar as Base;
use Staudenmeir\LaravelAdjacencyList\Query\Grammars\Traits\CompilesMySqlAdjacencyLists;

class MySqlGrammar extends Base implements ExpressionGrammar
{
    use CompilesMySqlAdjacencyLists;
}

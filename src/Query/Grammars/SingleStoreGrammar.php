<?php

namespace Staudenmeir\LaravelAdjacencyList\Query\Grammars;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use SingleStore\Laravel\Query\SingleStoreQueryGrammar;
use Staudenmeir\LaravelAdjacencyList\Query\Grammars\Traits\CompilesMySqlAdjacencyLists;

class SingleStoreGrammar extends SingleStoreQueryGrammar implements ExpressionGrammar
{
    use CompilesMySqlAdjacencyLists;

    /** @inheritDoc */
    public function __construct(Connection $connection, $ignoreOrderByInDeletes, $ignoreOrderByInUpdates, Model $model)
    {
        parent::__construct($connection, $ignoreOrderByInDeletes, $ignoreOrderByInUpdates);

        $this->model = $model;
    }

    /** @inheritDoc */
    public function compileInitialPath($column, $alias)
    {
        return 'cast(' . $this->wrap($column) . ' as char(8192)) as ' . $this->wrap($alias);
    }
}

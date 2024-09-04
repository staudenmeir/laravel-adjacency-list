<?php

namespace Staudenmeir\LaravelAdjacencyList\Query\Grammars;

use Illuminate\Database\Query\Grammars\MariaDbGrammar as Base;
use Staudenmeir\LaravelAdjacencyList\Query\Grammars\Traits\CompilesMySqlAdjacencyLists;

class MariaDbGrammar extends Base implements ExpressionGrammar
{
    use CompilesMySqlAdjacencyLists;

    /** @inheritDoc */
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
        '\\\\100000000000000000000\\\\2'
    ),
    '0+(\\\\d\{20\})([$pathSeparator]|$)',
    '\\\\1\\\\2'
) asc
SQL;
    }
}

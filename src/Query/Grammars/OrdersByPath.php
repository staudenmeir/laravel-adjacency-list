<?php

namespace Staudenmeir\LaravelAdjacencyList\Query\Grammars;

use Illuminate\Database\Eloquent\Model;

trait OrdersByPath
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Compile an "order by path" clause.
     *
     * @return string
     */
    public function compileOrderByPath()
    {
        $path = $this->model->getPathName();

        return $this->wrap($path) . ' asc';
    }
}

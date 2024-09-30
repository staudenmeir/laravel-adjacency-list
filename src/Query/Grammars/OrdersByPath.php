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

    /** @inheritDoc */
    public function compileOrderByPath()
    {
        $path = $this->model->getPathName();

        return $this->wrap($path) . ' asc';
    }
}

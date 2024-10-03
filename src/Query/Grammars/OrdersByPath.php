<?php

namespace Staudenmeir\LaravelAdjacencyList\Query\Grammars;

use Illuminate\Database\Eloquent\Model;

trait OrdersByPath
{
    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
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

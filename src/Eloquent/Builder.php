<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent;

use Illuminate\Database\Eloquent\Builder as Base;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Traits\BuildsAdjacencyListQueries;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends \Illuminate\Database\Eloquent\Builder<TModel>
 */
class Builder extends Base
{
    use BuildsAdjacencyListQueries;

    /**
     * The base query builder instance.
     *
     * @var \Staudenmeir\LaravelCte\Query\Builder
     */
    protected $query;

    /**
     * Get the underlying query builder instance.
     *
     * @return \Staudenmeir\LaravelCte\Query\Builder
     */
    public function getQuery()
    {
        return $this->query;
    }
}

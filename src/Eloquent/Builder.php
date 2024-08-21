<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent;

use Illuminate\Database\Eloquent\Builder as Base;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Traits\BuildsAdjacencyListQueries;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends Base<TModel>
 */
class Builder extends Base
{
    use BuildsAdjacencyListQueries;
}

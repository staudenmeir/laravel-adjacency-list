<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent;

use Illuminate\Database\Eloquent\Builder as Base;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Traits\BuildsAdjacencyListQueries;

class Builder extends Base
{
    use BuildsAdjacencyListQueries;
}

<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent;

use Staudenmeir\LaravelAdjacencyList\Eloquent\Traits\HasGraphAdjacencyList;
use Staudenmeir\LaravelCte\Eloquent\QueriesExpressions;

/**
 * @phpstan-ignore trait.unused
 */
trait HasGraphRelationships
{
    use HasGraphAdjacencyList;
    use QueriesExpressions;
}

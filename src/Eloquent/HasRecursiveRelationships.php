<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent;

use Staudenmeir\LaravelAdjacencyList\Eloquent\Traits\HasAdjacencyList;
use Staudenmeir\LaravelCte\Eloquent\QueriesExpressions;

/**
 * @phpstan-ignore trait.unused
 */
trait HasRecursiveRelationships
{
    use HasAdjacencyList;
    use QueriesExpressions;
}

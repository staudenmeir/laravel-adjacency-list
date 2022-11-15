<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Relations;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Staudenmeir\EloquentHasManyDeepContracts\Interfaces\ConcatenableRelation;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Traits\Concatenation\IsConcatenableAncestorsRelation;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Traits\IsAncestorRelation;

class Ancestors extends HasMany implements ConcatenableRelation
{
    use IsAncestorRelation;
    use IsConcatenableAncestorsRelation;
}

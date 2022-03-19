<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Relations;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Traits\IsAncestorRelation;

class Ancestors extends HasMany
{
    use IsAncestorRelation;
}

<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Relations;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Ancestors extends HasMany
{
    use IsAncestorRelation;
}

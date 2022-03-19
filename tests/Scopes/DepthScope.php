<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class DepthScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereDepth('<', 2);
    }

    public function extend(): void
    {
        //
    }
}

<?php

namespace Tests\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TestScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('id', '<', 8);
    }

    public function extend(): void
    {
        //
    }
}

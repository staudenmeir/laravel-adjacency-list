<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Traits\HasAdjacencyList;
use Staudenmeir\LaravelCte\Eloquent\QueriesExpressions;

trait HasRecursiveRelationships
{
    use HasAdjacencyList;
    use QueriesExpressions;

    public function isParentOf(Model $user)
    {
        return $this->children && $this->children->contains($user);
    }

    public function isChildOf(Model $user)
    {
        return $this->parent && $this->parent->is($user);
    }

    public function depthRelatedTo(Model $user)
    {
        return $this->ancestors && $this->ancestors->contains($user) ? $this->ancestors->indexOf($user) + 1 : null;
    }
}

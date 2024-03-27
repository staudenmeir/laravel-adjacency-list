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
        if (!$this->relationLoaded('children')) {
            $this->load('children');
        }
        return $this->children && $this->children->contains($user);
    }

    public function isChildOf(Model $user)
    {
        return $this->parent && $this->parent->is($user);
    }

    public function depthRelatedTo(Model $user)
    {
        if (!$this->relationLoaded('ancestors')) {
            $this->load('ancestors');
        }

        $index = $this->ancestors->search(function ($ancestor) use ($user) {
            return $ancestor->getKey() === $user->getKey();
        });

        return $index !== false ? $index + 1 : null;
    }
}

<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Traits\HasAdjacencyList;
use Staudenmeir\LaravelCte\Eloquent\QueriesExpressions;

trait HasRecursiveRelationships
{
    use HasAdjacencyList;
    use QueriesExpressions;

    public function isParentOf(Model $model)
    {
        if (!$this->relationLoaded('children')) {
            $this->load('children');
        }
        return $this->children ? $this->children->contains($model) : false;
    }

    public function isChildOf(Model $model)
    {
        if (!$this->relationLoaded('parent')) {
            $this->load('parent');
        }
        return $this->parent ? $this->parent->is($model) : false;
    }

    public function depthRelatedTo(Model $model)
    {
        if (!$this->relationLoaded('ancestors')) {
            $this->load('ancestors');
        }

        $index = $this->ancestors->search(function ($ancestor) use ($model) {
            return $ancestor->getKey() === $model->getKey();
        });

        return $index !== false ? $index + 1 : null;
    }
}

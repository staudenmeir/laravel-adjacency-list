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
        return $this->children->contains($model);
    }

    public function isChildOf(Model $model)
    {
        return $this->parent ? $this->parent->is($model) : false;
    }

    public function depthRelatedTo(Model $model)
    {
        $currentModel = $this->bloodline->firstWhere('id', $this->getKey());
        $relatedModel = $this->bloodline->firstWhere('id', $model->getKey());

        if ($currentModel && $relatedModel) {
            return $currentModel->depth - $relatedModel->depth;
        }

        return null;
    }
}

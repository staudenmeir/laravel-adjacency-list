<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Traits;

use Illuminate\Database\Eloquent\Model;

trait HasRecursiveRelationshipHelpers
{
    /**
     * Determine if the model is a child of the given model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return bool
     */
    public function isChildOf(Model $model): bool
    {
        return $this->parent ? $this->parent->is($model) : false;
    }

    /**
     * Determine if the model is the parent of the given model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return bool
     */
    public function isParentOf(Model $model): bool
    {
        return $this->children->contains($model);
    }

    /**
     * Get the depth of the model related to the given model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return int|null
     */
    public function getDepthRelatedTo(Model $model): ?int
    {
        $thisModel = $this->bloodline->find($this);
        $relatedModel = $this->bloodline->find($model);

        if ($thisModel && $relatedModel) {
            $depthName = $this->getDepthName();

            return $thisModel->$depthName - $relatedModel->$depthName;
        }

        return null;
    }
}

<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Graph\Traits\Concatenation;

trait IsConcatenableDescendantsRelation
{
    use IsConcatenableRelation;

    /**
     * Get the custom through key for an eager load of the relation.
     *
     * @param string $alias
     * @return array
     */
    public function getThroughKeyForDeepRelationships(string $alias): array
    {
        $path = $this->related->qualifyColumn(
            $this->related->getPathName()
        );

        $parentKey = $this->related->qualifyColumn(
            "pivot_" . $this->related->getParentKeyName()
        );

        return ["$path as {$alias}", "$parentKey as {$alias}_pivot_id"];
    }
}

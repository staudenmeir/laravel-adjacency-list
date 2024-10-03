<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Graph\Traits\Concatenation;

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 */
trait IsConcatenableAncestorsRelation
{
    /** @use \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Graph\Traits\Concatenation\IsConcatenableRelation<TRelatedModel, TDeclaringModel> */
    use IsConcatenableRelation;

    /**
     * Get the custom through key for an eager load of the relation.
     *
     * @param string $alias
     * @return array{string, string}
     */
    public function getThroughKeyForDeepRelationships(string $alias): array
    {
        $path = $this->related->qualifyColumn(
            $this->related->getPathName()
        );

        $childKey = $this->related->qualifyColumn(
            "pivot_" . $this->related->getChildKeyName()
        );

        return ["$path as {$alias}", "$childKey as {$alias}_pivot_id"];
    }
}

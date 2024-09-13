<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Graph;

use Illuminate\Database\Eloquent\Collection as Base;

/**
 * @template TKey of array-key
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends \Illuminate\Database\Eloquent\Collection<TKey, TModel>
 */
class Collection extends Base
{
    /**
     * Generate a nested tree.
     */
    public function toTree(string $childrenRelation = 'children'): static
    {
        if ($this->isEmpty()) {
            return $this;
        }

        /** @var TModel $model */
        $model = $this->first();

        $parentKeyName = $model->relationLoaded('pivot')
            ? 'pivot.' . $model->getParentKeyName()
            : 'pivot_' . $model->getParentKeyName();

        $localKeyName = $model->getLocalKeyName();

        $depthName = $model->getDepthName();

        $depths = $this->pluck($depthName);

        $tree = new static(
            $this->where($depthName, $depths->min())->values()
        );

        $itemsByParentKey = $this->groupBy($parentKeyName);

        foreach ($this->items as $item) {
            $item->setRelation(
                $childrenRelation,
                $itemsByParentKey[$item->$localKeyName] ?? new static()
            );
        }

        return $tree;
    }
}

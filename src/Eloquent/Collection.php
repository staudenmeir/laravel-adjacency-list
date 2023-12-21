<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent;

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
     *
     * @param string $childrenRelation
     * @return static<int, TModel>
     */
    public function toTree($childrenRelation = 'children')
    {
        if ($this->isEmpty()) {
            return $this;
        }

        $parentKeyName = $this->first()->getParentKeyName();
        $localKeyName = $this->first()->getLocalKeyName();
        $depthName = $this->first()->getDepthName();

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

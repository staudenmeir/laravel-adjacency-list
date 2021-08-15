<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent;

use Illuminate\Database\Eloquent\Collection as Base;

class Collection extends Base
{
    public function toTree($childrenRelation = 'children', $parentRelation = 'parent')
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
        $itemsByLocalKey = $this->keyBy($localKeyName);

        foreach ($this->items as $item) {
            $item->setRelation($childrenRelation, $itemsByParentKey[$item->$localKeyName] ?? new static());

            if (isset($item->$parentKeyName, $itemsByLocalKey[$item->$parentKeyName])) {
                $item->setRelation($parentRelation, $itemsByLocalKey[$item->$parentKeyName]);
            }
        }

        return $tree;
    }
}

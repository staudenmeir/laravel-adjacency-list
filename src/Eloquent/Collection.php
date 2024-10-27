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
     * @return static
     */
    public function toTree($childrenRelation = 'children')
    {
        if ($this->isEmpty()) {
            return $this;
        }

        $model = $this->first();

        /** @var string $parentKeyName */
        $parentKeyName = $model->getParentKeyName();

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

    /**
     * Load ancestor and parent relationships already present in the tree.
     *
     * @return static
     */
    public function loadTreeRelationships(): static
    {
        if ($this->isEmpty()) {
            return $this;
        }

        /** @var TModel $instance */
        $instance = $this->first();

        $keyName = $instance->getKeyName();
        $pathName = $instance->getPathName();
        $pathSeparator = $instance->getPathSeparator();

        /** @var static<TKey, TModel> $lookup */
        $lookup = $this->keyBy($keyName);

        /** @var \Illuminate\Support\Collection<int, string> $paths */
        $paths = $this->pluck($pathName);

        $keys = $paths
            ->flatMap(
                fn (string $path): array => explode($pathSeparator, $path)
            )->unique()
            ->values();

        $missing = $keys->diff(
            $lookup->modelKeys()
        );

        $lookup = $lookup->union(
            $instance->newQuery()->findMany($missing)->keyBy($keyName)
        );

        foreach ($this as $model) {
            $pathSegments = array_reverse(
                explode($pathSeparator, $model->$pathName)
            );

            $ancestorsAndSelf = array_reduce(
                $pathSegments,
                fn ($collection, string $key) => $collection->push($lookup[$key]),
                $instance->newCollection(),
            );

            $model->setRelation('ancestors', $ancestorsAndSelf->slice(1));
            $model->setRelation('ancestorsAndSelf', $ancestorsAndSelf);
            $model->setRelation('parent', count($pathSegments) > 1 ? $lookup[$pathSegments[1]] : null);
        }

        return $this;
    }
}

<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent;

use Illuminate\Database\Eloquent\Collection as Base;
use RuntimeException;

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
     * Load parent/anscestor relations already present in the tree.
     *
     * @return static<int, TModel>
     */
    public function loadTreePathRelations()
    {
        $instance = $this->first();

        if (is_null($instance)) {
            return $this;
        }

        if (! method_exists($instance, 'getPathName') || ! method_exists($instance, 'getPathSeparator')) {
            throw new RuntimeException(sprintf(
                'Model [%s] does not have recusive relations.',
                $instance::class,
            ));
        }

        $keyName       = $instance->getKeyName();
        $pathName      = $instance->getPathName();
        $pathSeparator = $instance->getPathSeparator();

        $lookup = $this->keyBy($keyName);

        $keys = $this
            ->pluck($pathName)
            ->flatMap(fn (string $path): array => explode($pathSeparator, $path))
            ->unique()
            ->values();

        $missing = $keys->diff($lookup->modelKeys());

        if ($missing->isNotEmpty()) {
            $lookup->merge($instance->newQuery()->findMany($missing)->keyBy($keyName));
        }

        foreach ($this->all() as $model) {
            $path = array_reverse(explode($pathSeparator, $model->getAttribute($pathName)));

            $ancestorsAndSelf = array_reduce(
                $path,
                fn ($collection, $step) => $collection->push($lookup[$step] ?? null),
                $instance->newCollection(),
            );

            $model->setRelation('parent', count($path) > 1 ? $lookup[$path[1]] : null);
            $model->setRelation('ancestorsAndSelf', $ancestorsAndSelf);
            $model->setRelation('ancestors', $ancestorsAndSelf->slice(0, -1));
        }

        return $this;
    }
}

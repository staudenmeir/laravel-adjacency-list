<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent;

use Illuminate\Database\Eloquent\Builder as Base;
use Illuminate\Database\PostgresConnection;

class Builder extends Base
{
    /**
     * Get the hydrated models without eager loading.
     *
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Model[]
     */
    public function getModels($columns = ['*'])
    {
        $items = $this->query->get($columns)->all();

        if ($this->getConnection() instanceof PostgresConnection) {
            $path = $this->model->getPathName();

            if (isset($items[0]->$path)) {
                $this->replacePathSeparator(
                    $items,
                    $path,
                    $this->model->getPathSeparator()
                );

                foreach ($this->model->getCustomPaths() as $path) {
                    $this->replacePathSeparator(
                        $items,
                        $path['name'],
                        $path['separator']
                    );
                }
            }
        }

        $table = (new $this->model)->getTable();

        $models = $this->model->hydrate($items)->each->setTable($table);

        return $models->all();
    }

    /**
     * Replace the separator in a PostgreSQL path column.
     *
     * @param array $items
     * @param string $path
     * @param string $separator
     * @return void
     */
    protected function replacePathSeparator(array $items, $path, $separator)
    {
        foreach ($items as $item) {
            $item->$path = str_replace(
                ',',
                $separator,
                substr($item->$path, 1, -1)
            );
        }
    }
}

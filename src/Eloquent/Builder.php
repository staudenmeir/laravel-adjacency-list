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
            $separator = $this->model->getPathSeparator();

            if (isset($items[0]->$path)) {
                foreach ($items as $item) {
                    $item->$path = str_replace(',', $separator, substr($item->$path, 1, -1));
                }
            }
        }

        $class = get_class($this->model);

        $table = (new $class)->getTable();

        $models = $this->model->hydrate($items)->each->setTable($table);

        return $models->all();
    }
}

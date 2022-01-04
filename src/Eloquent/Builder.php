<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent;

use Illuminate\Database\Eloquent\Builder as Base;
use Illuminate\Database\PostgresConnection;
use Illuminate\Support\Str;
use PDO;
use RuntimeException;
use Staudenmeir\LaravelAdjacencyList\Query\Grammars\MariaDbGrammar;
use Staudenmeir\LaravelAdjacencyList\Query\Grammars\MySqlGrammar;
use Staudenmeir\LaravelAdjacencyList\Query\Grammars\PostgresGrammar;
use Staudenmeir\LaravelAdjacencyList\Query\Grammars\SQLiteGrammar;
use Staudenmeir\LaravelAdjacencyList\Query\Grammars\SqlServerGrammar;

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

        $table = (new $this->model())->getTable();

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

    /**
     * Get the expression grammar.
     *
     * @return \Staudenmeir\LaravelAdjacencyList\Query\Grammars\ExpressionGrammar
     */
    public function getExpressionGrammar()
    {
        $driver = $this->query->getConnection()->getDriverName();

        switch ($driver) {
            case 'mysql':
                $version = $this->query->getConnection()->getReadPdo()->getAttribute(PDO::ATTR_SERVER_VERSION);

                $grammar = Str::contains($version, 'MariaDB')
                    ? new MariaDbGrammar($this->model)
                    : new MySqlGrammar($this->model);

                return $this->query->getConnection()->withTablePrefix($grammar);
            case 'pgsql':
                return $this->query->getConnection()->withTablePrefix(
                    new PostgresGrammar($this->model)
                );
            case 'sqlite':
                return $this->query->getConnection()->withTablePrefix(
                    new SQLiteGrammar($this->model)
                );
            case 'sqlsrv':
                return $this->query->getConnection()->withTablePrefix(
                    new SqlServerGrammar($this->model)
                );
        }

        throw new RuntimeException('This database is not supported.'); // @codeCoverageIgnore
    }

    /**
     * Register all passed global scopes.
     *
     * @param array $scopes
     * @return $this
     */
    public function withGlobalScopes(array $scopes)
    {
        foreach ($scopes as $identifier => $scope) {
            $this->withGlobalScope($identifier, $scope);
        }

        return $this;
    }
}

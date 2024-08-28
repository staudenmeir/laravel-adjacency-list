<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Traits;

use Illuminate\Database\PostgresConnection;
use RuntimeException;
use Staudenmeir\LaravelAdjacencyList\Query\Grammars\FirebirdGrammar;
use Staudenmeir\LaravelAdjacencyList\Query\Grammars\MariaDbGrammar;
use Staudenmeir\LaravelAdjacencyList\Query\Grammars\MySqlGrammar;
use Staudenmeir\LaravelAdjacencyList\Query\Grammars\PostgresGrammar;
use Staudenmeir\LaravelAdjacencyList\Query\Grammars\SingleStoreGrammar;
use Staudenmeir\LaravelAdjacencyList\Query\Grammars\SQLiteGrammar;
use Staudenmeir\LaravelAdjacencyList\Query\Grammars\SqlServerGrammar;

trait BuildsAdjacencyListQueries
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
        /** @var \Illuminate\Database\Connection $connection */
        $connection = $this->query->getConnection();

        switch ($connection->getDriverName()) {
            case 'mysql':
                /** @var \Illuminate\Database\MySqlConnection $connection */
                $grammar = $connection->isMaria()
                    ? new MariaDbGrammar($this->model)
                    : new MySqlGrammar($this->model);

                return $connection->withTablePrefix($grammar);
            case 'mariadb':
                return $connection->withTablePrefix(
                    new MariaDbGrammar($this->model)
                );
            case 'pgsql':
                return $connection->withTablePrefix(
                    new PostgresGrammar($this->model)
                );
            case 'sqlite':
                return $connection->withTablePrefix(
                    new SQLiteGrammar($this->model)
                );
            case 'sqlsrv':
                return $connection->withTablePrefix(
                    new SqlServerGrammar($this->model)
                );
            case 'singlestore':
                return $connection->withTablePrefix(
                    new SingleStoreGrammar($this->model)
                );
            case 'firebird':
                return $connection->withTablePrefix(
                    new FirebirdGrammar($this->model)
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

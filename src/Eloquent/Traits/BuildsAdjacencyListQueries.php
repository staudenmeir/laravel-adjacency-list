<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Traits;

use Illuminate\Database\MySqlConnection;
use Illuminate\Database\PostgresConnection;
use RuntimeException;
use Staudenmeir\LaravelAdjacencyList\Query\Grammars\FirebirdGrammar;
use Staudenmeir\LaravelAdjacencyList\Query\Grammars\MariaDbGrammar;
use Staudenmeir\LaravelAdjacencyList\Query\Grammars\MySqlGrammar;
use Staudenmeir\LaravelAdjacencyList\Query\Grammars\PostgresGrammar;
use Staudenmeir\LaravelAdjacencyList\Query\Grammars\SingleStoreGrammar;
use Staudenmeir\LaravelAdjacencyList\Query\Grammars\SQLiteGrammar;
use Staudenmeir\LaravelAdjacencyList\Query\Grammars\SqlServerGrammar;

/**
 * @mixin \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder
 */
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

        if ($connection instanceof MySqlConnection) {
            /** @var \Staudenmeir\LaravelAdjacencyList\Query\Grammars\ExpressionGrammar $grammar */
            $grammar = $connection->withTablePrefix($connection->isMaria()
                ? new MariaDbGrammar($this->model)
                : new MySqlGrammar($this->model));

            return $grammar;
        }

        /** @var \Staudenmeir\LaravelAdjacencyList\Query\Grammars\ExpressionGrammar $grammar */
        $grammar = match ($connection->getDriverName()) {
            'mariadb' => $connection->withTablePrefix(
                new MariaDbGrammar($this->model)
            ),
            'pgsql' => $connection->withTablePrefix(
                new PostgresGrammar($this->model)
            ),
            'sqlite' => $connection->withTablePrefix(
                new SQLiteGrammar($this->model)
            ),
            'sqlsrv' => $connection->withTablePrefix(
                new SqlServerGrammar($this->model)
            ),
            'singlestore' => $connection->withTablePrefix(
                new SingleStoreGrammar($this->model)
            ),
            'firebird' => $connection->withTablePrefix(
                new FirebirdGrammar($this->model)
            ),
            default => throw new RuntimeException('This database is not supported.'),
        };

        return $grammar;
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

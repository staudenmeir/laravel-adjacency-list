<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Traits;

use Illuminate\Database\Connection;
use Illuminate\Database\PostgresConnection;
use RuntimeException;
use Staudenmeir\LaravelAdjacencyList\Query\Grammars\ExpressionGrammar;
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
     * @param list<string|\Illuminate\Database\Query\Expression>|string $columns
     * @return list<\Illuminate\Database\Eloquent\Model>
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
     * @param list<object> $items
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

        return match ($connection->getDriverName()) {
            'mysql' => $this->getMySqlExpressionGrammar($connection),
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
    }

    /**
     * Get the MySQL expression grammar.
     *
     * @param \Illuminate\Database\Connection $connection
     * @return \Staudenmeir\LaravelAdjacencyList\Query\Grammars\ExpressionGrammar
     */
    protected function getMySqlExpressionGrammar(Connection $connection): ExpressionGrammar
    {
        /**
         * @var \Illuminate\Database\MySqlConnection $connection
         * @var \Staudenmeir\LaravelAdjacencyList\Query\Grammars\ExpressionGrammar $grammar
         */
        $grammar = $connection->withTablePrefix(
            $connection->isMaria()
                ? new MariaDbGrammar($this->model)
                : new MySqlGrammar($this->model)
        );

        return $grammar;
    }

    /**
     * Register all passed global scopes.
     *
     * @param array<string, \Closure|\Illuminate\Database\Eloquent\Scope> $scopes
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

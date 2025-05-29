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
     * @param list<string|\Illuminate\Database\Query\Expression<*>>|string $columns
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
     * @param array<int, object> $items
     * @param string $path
     * @param string $separator
     * @return void
     */
    protected function replacePathSeparator(array $items, $path, $separator)
    {
        foreach ($items as $item) {
            if (str_contains($item->$path, '"')) {
                $item->$path = implode(
                    $separator,
                    str_getcsv(
                        trim($item->$path, '{}')
                    )
                );
            } else {
                $item->$path = str_replace(
                    ',',
                    $separator,
                    trim($item->$path, '{}')
                );
            }
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
            'mysql' => $connection->isMaria()
                ? new MariaDbGrammar($connection, $this->model)
                : new MySqlGrammar($connection, $this->model),
            'mariadb' => new MariaDbGrammar($connection, $this->model),
            'pgsql' => new PostgresGrammar($connection, $this->model),
            'sqlite' => new SQLiteGrammar($connection, $this->model),
            'sqlsrv' => new SqlServerGrammar($connection, $this->model),
            'singlestore' => new SingleStoreGrammar(
                connection: $connection,
                ignoreOrderByInDeletes: $connection->getConfig('ignore_order_by_in_deletes'),
                ignoreOrderByInUpdates: $connection->getConfig('ignore_order_by_in_updates'),
                model: $this->model,
            ),
            'firebird' => new FirebirdGrammar($connection, $this->model),
            default => throw new RuntimeException('This database is not supported.'),
        };
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

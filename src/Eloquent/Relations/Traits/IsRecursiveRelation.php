<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;

trait IsRecursiveRelation
{
    /**
     * Whether to include the parent model.
     *
     * @var bool
     */
    protected $andSelf;

    /**
     * Create a new recursive relationship instance.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model $parent
     * @param string $foreignKey
     * @param string $localKey
     * @param bool $andSelf
     * @return void
     */
    public function __construct(Builder $query, Model $parent, $foreignKey, $localKey, $andSelf)
    {
        $this->andSelf = $andSelf;

        parent::__construct($query, $parent, $foreignKey, $localKey);
    }

    /**
     * Build model dictionary.
     *
     * @param \Illuminate\Database\Eloquent\Collection $results
     * @return array
     */
    protected function buildDictionary(Collection $results)
    {
        return $results->mapToDictionary(function (Model $result) {
            return [$result->getFirstPathSegment() => $result];
        })->all();
    }

    /**
     * Get the fully qualified local key name.
     *
     * @return string
     */
    public function getQualifiedLocalKeyName()
    {
        return $this->qualifyColumn($this->localKey);
    }

    /**
     * Update records in the database.
     *
     * @param array $values
     * @return int
     */
    public function update(array $values)
    {
        return $this->executeUpdateOrDeleteQuery(__FUNCTION__, func_get_args());
    }

    /**
     * Decrement a column's value by a given amount.
     *
     * @param string $column
     * @param float|int $amount
     * @param array $extra
     * @return int
     */
    public function increment($column, $amount = 1, array $extra = [])
    {
        return $this->executeUpdateOrDeleteQuery(__FUNCTION__, func_get_args());
    }

    /**
     * Decrement a column's value by a given amount.
     *
     * @param string|\Illuminate\Database\Query\Expression $column
     * @param float|int $amount
     * @param array $extra
     * @return int
     */
    public function decrement($column, $amount = 1, array $extra = [])
    {
        return $this->executeUpdateOrDeleteQuery(__FUNCTION__, func_get_args());
    }

    /**
     * Delete records from the database.
     *
     * @return mixed
     */
    public function delete()
    {
        return $this->executeUpdateOrDeleteQuery(__FUNCTION__);
    }

    /**
     * Force a hard delete on a soft deleted model.
     *
     * @return bool|null
     */
    public function forceDelete()
    {
        return $this->executeUpdateOrDeleteQuery(__FUNCTION__);
    }

    /**
     * Execute an update or delete query after adding the necessary "where in" constraint.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function executeUpdateOrDeleteQuery($method, array $parameters = [])
    {
        $expression = $this->query->getQuery()->from;

        $table = $this->parent->getTable();

        $this->query->getQuery()->from = $table;

        $this->query->getModel()->setTable($table);

        $keys = $this->query->getQuery()->newQuery()->from($expression)->select($this->localKey);

        return $this->query->whereIn($this->getQualifiedLocalKeyName(), $keys)->$method(...$parameters);
    }

    /**
     * Replace table hash with expression name in self-relation aggregate queries.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Query\Expression $expression
     * @return \Illuminate\Database\Query\Expression
     */
    protected function replaceTableHash(Builder $query, Expression $expression)
    {
        return new Expression(
            str_replace(
                $query->getGrammar()->wrap(
                    $this->getRelationCountHash(false)
                ),
                $query->getGrammar()->wrap(
                    $query->getModel()->getExpressionName()
                ),
                $expression->getValue(),
            )
        );
    }
}

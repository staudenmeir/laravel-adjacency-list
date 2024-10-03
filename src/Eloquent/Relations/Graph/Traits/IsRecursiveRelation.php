<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Graph\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 */
trait IsRecursiveRelation
{
    /**
     * Whether to include the parent model.
     *
     * @var bool
     */
    protected bool $andSelf;

    /**
     * Create a new recursive pivot relationship instance.
     *
     * @param \Illuminate\Database\Eloquent\Builder<TRelatedModel> $query
     * @param TDeclaringModel $parent
     * @param string $table
     * @param string $foreignPivotKey
     * @param string $relatedPivotKey
     * @param string $parentKey
     * @param string $relatedKey
     * @param bool $andSelf
     * @return void
     */
    public function __construct(
        Builder $query,
        Model $parent,
        string $table,
        string $foreignPivotKey,
        string $relatedPivotKey,
        string $parentKey,
        string $relatedKey,
        bool $andSelf
    ) {
        $this->andSelf = $andSelf;

        parent::__construct($query, $parent, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey);
    }

    /**
     * Add the recursive expression for an eager load of the relation.
     *
     * @param list<TDeclaringModel> $models
     * @param string $column
     * @return void
     */
    protected function addEagerExpression(array $models, string $column): void
    {
        $whereIn = $this->whereInMethod($this->parent, $this->parentKey);

        $keys = $this->getKeys($models, $this->parentKey);

        $constraint = function (Builder $query) use ($whereIn, $column, $keys) {
            $query->$whereIn($column, $keys);
        };

        $supportsUnion = $this->query->getExpressionGrammar()->supportsUnionInRecursiveExpression();

        $union = $this->andSelf || !$supportsUnion ? 'unionAll' : 'union';

        if (!$supportsUnion) {
            $this->query->getQuery()->distinct();
        }

        $this->addExpression($constraint, null, null, $union);
    }

    /**
     * Build model dictionary.
     *
     * @param \Illuminate\Database\Eloquent\Collection<array-key, \Illuminate\Database\Eloquent\Model> $results
     * @return array<int|string, list<\Illuminate\Database\Eloquent\Model>>
     */
    protected function buildDictionary(Collection $results)
    {
        return $results->mapToDictionary(function (Model $result) {
            return [$result->getFirstPathSegment() => $result];
        })->all();
    }

    /**
     * Handle dynamic method calls to the relationship.
     *
     * @param string $method
     * @param array<int|string, mixed> $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $methods = ['update', 'increment', 'decrement', 'delete', 'forceDelete'];

        if (in_array($method, $methods)) {
            $expression = $this->query->getQuery()->from;

            $table = $this->parent->getTable();

            $this->query->getQuery()->from = $table;

            $this->query->getModel()->setTable($table);

            $keys = $this->query->getQuery()->newQuery()->from($expression)->select($this->parentKey);

            return $this->query->whereIn($this->parentKey, $keys)->$method(...$parameters);
        }

        return parent::__call($method, $parameters);
    }

    /**
     * Replace table hash with expression name in self-relation aggregate queries.
     *
     * @param \Illuminate\Database\Eloquent\Builder<*> $query
     * @param \Illuminate\Database\Query\Expression $expression
     * @return \Illuminate\Database\Query\Expression
     */
    protected function replaceTableHash(Builder $query, Expression $expression): Expression
    {
        return new Expression(
            str_replace(
                $query->getGrammar()->wrap(
                    $this->getRelationCountHash(false)
                ),
                $query->getGrammar()->wrap(
                    $query->getModel()->getExpressionName()
                ),
                (string) $expression->getValue(
                    $query->getGrammar()
                )
            )
        );
    }

    /**
     * Set the select clause for the relation query.
     *
     * @param list<string> $columns
     * @return list<string>
     */
    protected function shouldSelect(array $columns = ['*'])
    {
        return $columns;
    }
}

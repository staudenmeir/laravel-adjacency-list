<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Staudenmeir\LaravelAdjacencyList\Query\Grammars\ExpressionGrammar;

class HasManyOfDescendants extends HasMany
{
    /**
     * Whether to include the parent model.
     *
     * @var bool
     */
    protected $andSelf;

    /**
     * The path list column alias.
     *
     * @var string
     */
    protected $pathListAlias = 'laravel_paths';

    /**
     * Create a new has many of descendants relationship instance.
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
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
        if (static::$constraints) {
            $column = $this->andSelf ? $this->parent->getLocalKeyName() : $this->parent->getParentKeyName();

            $constraint = function (Builder $query) use ($column) {
                $query->where(
                    $column,
                    '=',
                    $this->parent->{$this->parent->getLocalKeyName()}
                )->whereNotNull($column);
            };

            $this->addExpression($constraint);
        }
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param array $models
     * @return void
     */
    public function addEagerConstraints(array $models)
    {
        $localKey = $this->parent->getLocalKeyName();

        $whereIn = $this->whereInMethod($this->parent, $localKey);

        $keys = $this->getKeys($models, $localKey);

        $constraint = function (Builder $query) use ($keys, $localKey, $models, $whereIn) {
            $query->$whereIn($localKey, $keys);
        };

        $grammar = $this->getExpressionGrammar();

        $pathSeparator = $this->parent->getPathSeparator();
        $listSeparator = $this->getPathListSeparator();

        $pathList = $grammar->selectPathList(
            $this->query->getQuery()->newQuery(),
            $this->parent->getExpressionName(),
            $this->parent->getPathName(),
            $pathSeparator,
            $listSeparator
        );

        $this->addExpression($constraint, null, null, true)
            ->select($this->query->getQuery()->from.'.*')
            ->selectSub($pathList, $this->pathListAlias);
    }

    /**
     * Build model dictionary.
     *
     * @param \Illuminate\Database\Eloquent\Collection $results
     * @return array
     */
    protected function buildDictionary(Collection $results)
    {
        $dictionary = [];

        $foreignKey = $this->getForeignKeyName();

        $pathSeparator = $this->parent->getPathSeparator();
        $pathListSeparator = $this->getPathListSeparator();

        foreach ($results as $result) {
            $paths = explode($pathListSeparator, $result->{$this->pathListAlias});

            foreach ($paths as $path) {
                $isDescendant = Str::endsWith($path, $pathSeparator.$result->$foreignKey);

                if ($this->andSelf) {
                    if (!$isDescendant && $path !== (string) $result->$foreignKey) {
                        continue;
                    }
                } elseif (!$isDescendant) {
                    continue;
                }

                $keys = explode($pathSeparator, $path);

                if (!$this->andSelf) {
                    array_pop($keys);
                }

                foreach ($keys as $key) {
                    if (!isset($dictionary[$key])) {
                        $dictionary[$key] = new Collection();
                    }

                    if (!$dictionary[$key]->contains($result)) {
                        $dictionary[$key][] = $result;
                    }
                }
            }

            unset($result->{$this->pathListAlias});
        }

        foreach ($dictionary as $key => $results) {
            $dictionary[$key] = $results->all();
        }

        return $dictionary;
    }

    /**
     * Add the constraints for a relationship query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Builder $parentQuery
     * @param array|mixed $columns
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        $table = (new $this->parent())->getTable();

        if ($table === $parentQuery->getQuery()->from) {
            $table = $alias = $this->getRelationCountHash();
        } else {
            $alias = null;
        }

        $first = $this->andSelf
            ? $this->parent->getLocalKeyName()
            : $this->parent->getParentKeyName();

        $constraint = function (Builder $query) use ($first, $table) {
            $query->whereColumn(
                $table.'.'.$first,
                '=',
                $this->parent->getQualifiedLocalKeyName()
            );
        };

        return $this->addExpression($constraint, $query->select($columns), $alias);
    }

    /**
     * Add a recursive expression to the query.
     *
     * @param callable $constraint
     * @param \Illuminate\Database\Eloquent\Builder|null $query
     * @param string|null $alias
     * @param bool $selectPath
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function addExpression(callable $constraint, Builder $query = null, $alias = null, $selectPath = false)
    {
        $name = $this->parent->getExpressionName();

        $query = $query ?: $this->query;

        $grammar = $this->getExpressionGrammar();

        $expression = $this->getInitialQuery($grammar, $constraint, $alias, $selectPath)
            ->unionAll(
                $this->getRecursiveQuery($grammar, $selectPath)
            );

        $query->withRecursiveExpression($name, $expression);

        $query->withGlobalScope('HasManyOfDescendants', function (Builder $query) use ($name) {
            $query->whereIn(
                $this->foreignKey,
                (new $this->parent())->setTable($name)->newQuery()->select($this->localKey)
            );
        });

        return $query;
    }

    /**
     * Get the initial query for a relationship expression.
     *
     * @param \Staudenmeir\LaravelAdjacencyList\Query\Grammars\ExpressionGrammar $grammar
     * @param callable $constraint
     * @param string|null $alias
     * @param bool $selectPath
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    protected function getInitialQuery(ExpressionGrammar $grammar, callable $constraint, $alias, $selectPath)
    {
        $model = new $this->parent();
        $query = $model->newModelQuery();

        if ($alias) {
            $query->from($query->getQuery()->from, $alias);
        }

        $constraint($query);

        if ($selectPath) {
            $initialPath = $grammar->compileInitialPath(
                $this->localKey,
                $model->getPathName()
            );

            $query->select('*')->selectRaw($initialPath);
        }

        return $query;
    }

    /**
     * Get the recursive query for a relationship expression.
     *
     * @param \Staudenmeir\LaravelAdjacencyList\Query\Grammars\ExpressionGrammar $grammar
     * @param bool $selectPath
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    protected function getRecursiveQuery(ExpressionGrammar $grammar, $selectPath)
    {
        $model = new $this->parent();
        $name = $model->getExpressionName();
        $query = $model->newModelQuery();

        $query->select($query->getQuery()->from.'.*')
            ->join(
                $name,
                $name.'.'.$model->getLocalKeyName(),
                '=',
                $query->qualifyColumn($model->getParentKeyName())
            );

        if ($selectPath) {
            $recursivePath = $grammar->compileRecursivePath(
                $model->qualifyColumn($this->localKey),
                $model->getPathName()
            );

            $recursivePathBindings = $grammar->getRecursivePathBindings($model->getPathSeparator());

            $query->selectRaw($recursivePath, $recursivePathBindings);
        }

        return $query;
    }

    /**
     * Get the expression grammar
     *
     * @return \Staudenmeir\LaravelAdjacencyList\Query\Grammars\ExpressionGrammar
     */
    protected function getExpressionGrammar()
    {
        return $this->parent->newQuery()->getExpressionGrammar();
    }

    /**
     * Get the path list separator.
     *
     * @return string
     */
    protected function getPathListSeparator()
    {
        return str_repeat(
            $this->parent->getPathSeparator(),
            2
        );
    }

    /**
     * Include trashed descendants in the query.
     *
     * @return $this
     */
    public function withTrashedDescendants()
    {
        $table = $this->parent->getExpressionName();

        $this->query->withoutGlobalScope('HasManyOfDescendants')
            ->whereIn(
                $this->foreignKey,
                (new $this->parent())->setTable($table)->newModelQuery()->select($this->localKey)
            );

        return $this;
    }
}

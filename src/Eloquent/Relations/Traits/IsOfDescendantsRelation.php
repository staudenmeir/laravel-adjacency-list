<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Staudenmeir\LaravelAdjacencyList\Query\Grammars\ExpressionGrammar;

trait IsOfDescendantsRelation
{
    use TracksIntermediateScopes;

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

    /** @inheritDoc */
    public function addConstraints()
    {
        if (static::$constraints) {
            $constraint = function (Builder $query) {
                $this->addExpressionWhereConstraints($query);
            };

            $this->addExpression($constraint);
        }
    }

    /**
     * Set the where clause on the recursive expression query.
     *
     * @param \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<\Illuminate\Database\Eloquent\Model> $query
     * @return void
     */
    abstract public function addExpressionWhereConstraints(Builder $query);

    /** @inheritDoc */
    public function addEagerConstraints(array $models)
    {
        $constraint = function (Builder $query) use ($models) {
            $this->addEagerExpressionWhereConstraints($query, $models);
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
     * Set the where clause on the recursive expression query for an eager load of the relation.
     *
     * @param \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<\Illuminate\Database\Eloquent\Model> $query
     * @param \Illuminate\Database\Eloquent\Model[] $models
     * @return void
     */
    public function addEagerExpressionWhereConstraints(Builder $query, array $models)
    {
        $localKeyName = $this->getEagerLoadingLocalKeyName();

        $whereIn = $this->whereInMethod($this->parent, $localKeyName);

        $keys = $this->getKeys($models, $localKeyName);

        $query->$whereIn($localKeyName, $keys);
    }

    /** @inheritDoc */
    protected function buildDictionary(Collection $results)
    {
        $dictionary = [];

        if ($results->isEmpty()) {
            return $dictionary;
        }

        $paths = explode(
            $this->getPathListSeparator(),
            $results[0]->{$this->pathListAlias}
        );

        $foreignKeyName = $this->getEagerLoadingForeignKeyName();
        $accessor = $this->getEagerLoadingAccessor();
        $pathSeparator = $this->parent->getPathSeparator();

        foreach ($results as $result) {
            foreach ($paths as $path) {
                if (!$this->pathBelongsToResult($result, $foreignKeyName, $accessor, $pathSeparator, $path)) {
                    continue;
                }

                $dictionary = $this->addResultToDictionary($dictionary, $result, $pathSeparator, $path);
            }

            unset($result->{$this->pathListAlias});
        }

        foreach ($dictionary as $key => $values) {
            $dictionary[$key] = $values->all();
        }

        return $dictionary;
    }

    /**
     * Determine whether a path belongs to a result.
     *
     * @param \Illuminate\Database\Eloquent\Model $result
     * @param string $foreignKeyName
     * @param string $accessor
     * @param string $pathSeparator
     * @param string $path
     * @return bool
     */
    protected function pathBelongsToResult(Model $result, $foreignKeyName, $accessor, $pathSeparator, $path)
    {
        $foreignKey = (string) ($accessor ? $result->$accessor : $result)->$foreignKeyName;

        $isDescendant = Str::endsWith($path, $pathSeparator.$foreignKey);

        if ($this->andSelf) {
            if ($isDescendant || $path === $foreignKey) {
                return true;
            }
        } elseif ($isDescendant) {
            return true;
        }

        return false;
    }

    /**
     * Add a result to the dictionary.
     *
     * @param array $dictionary
     * @param \Illuminate\Database\Eloquent\Model $result
     * @param string $pathSeparator
     * @param string $path
     * @return array
     */
    protected function addResultToDictionary(array $dictionary, Model $result, $pathSeparator, $path)
    {
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

        return $dictionary;
    }

    /**
     * Get the local key name for an eager load of the relation.
     *
     * @return string
     */
    abstract public function getEagerLoadingLocalKeyName();

    /**
     * Get the foreign key name for an eager load of the relation.
     *
     * @return string
     */
    abstract public function getEagerLoadingForeignKeyName();

    /**
     * Get the accessor for an eager load of the relation.
     *
     * @return string|null
     */
    public function getEagerLoadingAccessor()
    {
        return null;
    }

    /**
     * Add a recursive expression to the query.
     *
     * @param callable $constraint
     * @param \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>|null $query
     * @param string|null $alias
     * @param bool $selectPath
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>
     */
    protected function addExpression(callable $constraint, ?Builder $query = null, $alias = null, $selectPath = false)
    {
        $name = $this->parent->getExpressionName();

        /** @var \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<\Illuminate\Database\Eloquent\Model> $query */
        $query = $query ?: $this->query;

        $grammar = $this->getExpressionGrammar();

        $expression = $this->getInitialQuery($grammar, $constraint, $alias, $selectPath)
            ->unionAll(
                $this->getRecursiveQuery($grammar, $selectPath)
            );

        $query->getQuery()->withRecursiveExpression($name, $expression->getQuery());

        $query->withGlobalScope(get_class($this), function (Builder $query) use ($name) {
            $query->whereIn(
                $this->getExpressionForeignKeyName(),
                (new $this->parent())->setTable($name)
                    ->newQuery()
                    ->withGlobalScopes($this->intermediateScopes)
                    ->withoutGlobalScopes($this->removedIntermediateScopes)
                    ->select($this->getExpressionLocalKeyName())
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
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>
     */
    protected function getInitialQuery(ExpressionGrammar $grammar, callable $constraint, $alias, $selectPath)
    {
        $model = new $this->parent();

        $initialDepth = $this->andSelf ? 0 : 1;

        $depth = $grammar->wrap($model->getDepthName());

        $query = $model->newModelQuery();

        $table = $alias ?: $query->getQuery()->from;

        $query->select("$table.*")
            ->selectRaw("$initialDepth as $depth");

        if ($alias) {
            $query->from($query->getQuery()->from, $alias);
        }

        $constraint($query);

        if ($selectPath) {
            $initialPath = $grammar->compileInitialPath(
                $this->getExpressionLocalKeyName(),
                $model->getPathName()
            );

            $query->selectRaw($initialPath);
        }

        return $query;
    }

    /**
     * Get the recursive query for a relationship expression.
     *
     * @param \Staudenmeir\LaravelAdjacencyList\Query\Grammars\ExpressionGrammar $grammar
     * @param bool $selectPath
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>
     */
    protected function getRecursiveQuery(ExpressionGrammar $grammar, $selectPath)
    {
        $model = new $this->parent();

        $name = $model->getExpressionName();

        $depth = $grammar->wrap($model->getDepthName());

        $recursiveDepth = "$depth + 1";

        $query = $model->newModelQuery();

        $query->select($query->getQuery()->from.'.*')
            ->selectRaw("$recursiveDepth as $depth")
            ->join(
                $name,
                $name.'.'.$model->getLocalKeyName(),
                '=',
                $query->qualifyColumn($model->getParentKeyName())
            );

        if ($selectPath) {
            $recursivePath = $grammar->compileRecursivePath(
                $model->qualifyColumn(
                    $this->getExpressionLocalKeyName()
                ),
                $model->getPathName()
            );

            $recursivePathBindings = $grammar->getRecursivePathBindings($model->getPathSeparator());

            $query->selectRaw($recursivePath, $recursivePathBindings);
        }

        return $query;
    }

    /**
     * Get the local key name for the recursion expression.
     *
     * @return string
     */
    abstract public function getExpressionLocalKeyName();

    /**
     * Get the foreign key name for the recursion expression.
     *
     * @return string
     */
    abstract public function getExpressionForeignKeyName();

    /**
     * Add the constraints for a relationship query.
     *
     * @param \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<\Illuminate\Database\Eloquent\Model> $query
     * @param \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<\Illuminate\Database\Eloquent\Model> $parentQuery
     * @param array|mixed $columns
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>
     */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        $table = (new $this->parent())->getTable();

        if ($table === $parentQuery->getQuery()->from) {
            $table = $alias = $this->getRelationCountHash();
        } else {
            $alias = null;
        }

        $constraint = function (Builder $query) use ($table) {
            $this->addExistenceExpressionWhereConstraints($query, $table);
        };

        $query->select($columns);

        return $this->addExpression($constraint, $query, $alias);
    }

    /**
     * Set the where clause on the recursive expression query for an existence query.
     *
     * @param \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<\Illuminate\Database\Eloquent\Model> $query
     * @param string $table
     * @return void
     */
    public function addExistenceExpressionWhereConstraints(Builder $query, $table)
    {
        $first = $this->andSelf
            ? $this->parent->getLocalKeyName()
            : $this->parent->getParentKeyName();

        $query->whereColumn(
            $table.'.'.$first,
            '=',
            $this->parent->getQualifiedLocalKeyName()
        );
    }

    /**
     * Get the expression grammar.
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
}

<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Relations;

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

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
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
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return void
     */
    abstract public function addExpressionWhereConstraints(Builder $query);

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param array $models
     * @return void
     */
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
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $models
     * @return void
     */
    public function addEagerExpressionWhereConstraints(Builder $query, array $models)
    {
        $localKeyName = $this->getEagerLoadingLocalKeyName();

        $whereIn = $this->whereInMethod($this->parent, $localKeyName);

        $keys = $this->getKeys($models, $localKeyName);

        $query->$whereIn($localKeyName, $keys);
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

        $paths = explode(
            $this->getPathListSeparator(),
            $results[0]->{$this->pathListAlias}
        );

        $foreignKeyName = $this->getEagerLoadingForeignKeyName();
        $accessor = $this->getEagerLoadingAccessor();
        $pathSeparator = $this->parent->getPathSeparator();

        foreach ($results as $result) {
            foreach ($paths as $path) {
                if (!$this->pathMatches($result, $foreignKeyName, $accessor, $pathSeparator, $path)) {
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
     * Determine whether a path belongs to a result.
     *
     * @param \Illuminate\Database\Eloquent\Model $result
     * @param string $foreignKeyName
     * @param string $accessor
     * @param string $pathSeparator
     * @param string $path
     * @return bool
     */
    protected function pathMatches(Model $result, $foreignKeyName, $accessor, $pathSeparator, $path)
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

        $query->withGlobalScope(get_class(), function (Builder $query) use ($name) {
            $query->whereIn(
                $this->getExpressionForeignKeyName(),
                (new $this->parent())->setTable($name)
                    ->newQuery()
                    ->select($this->getExpressionLocalKeyName())
                    ->withGlobalScopes($this->intermediateScopes)
                    ->withoutGlobalScopes($this->removedIntermediateScopes)
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

        $initialDepth = $this->andSelf ? 0 : 1;

        $depth = $grammar->wrap($model->getDepthName());

        $query = $model->newModelQuery()
            ->select('*')
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
     * @return \Illuminate\Database\Eloquent\Builder $query
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

        $constraint = function (Builder $query) use ($table) {
            $this->addExistenceExpressionWhereConstraints($query, $table);
        };

        return $this->addExpression($constraint, $query->select($columns), $alias);
    }

    /**
     * Set the where clause on the recursive expression query for an existence query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
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

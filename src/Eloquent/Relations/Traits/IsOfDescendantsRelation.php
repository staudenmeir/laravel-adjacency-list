<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Builder as AdjacencyListBuilder;
use Staudenmeir\LaravelAdjacencyList\Query\Grammars\ExpressionGrammar;

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 */
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
            $constraint = function (AdjacencyListBuilder $query) {
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
        $constraint = function (AdjacencyListBuilder $query) use ($models) {
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

        /** @var string $from */
        $from = $this->query->getQuery()->from;

        $this->addExpression($constraint, null, null, true)
            ->select("$from.*")
            ->selectSub($pathList, $this->pathListAlias);
    }

    /**
     * Set the where clause on the recursive expression query for an eager load of the relation.
     *
     * @param \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<\Illuminate\Database\Eloquent\Model> $query
     * @param list<TDeclaringModel> $models
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
     * Build model dictionary keyed by the relation's foreign key.
     *
     * @param \Illuminate\Database\Eloquent\Collection<int, TRelatedModel> $results
     * @return array<int|string, array<int, TRelatedModel>>|array<array<string, TRelatedModel>>
     */
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

        /** @var array<int|string, array<int, TRelatedModel>>|array<array<string, TRelatedModel>> $dictionary */
        return $dictionary;
    }

    /**
     * Determine whether a path belongs to a result.
     *
     * @param \Illuminate\Database\Eloquent\Model $result
     * @param string $foreignKeyName
     * @param string|null $accessor
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
     * @param array<int|string, \Illuminate\Database\Eloquent\Collection<int, TRelatedModel>> $dictionary
     * @param TRelatedModel $result
     * @param non-empty-string $pathSeparator
     * @param string $path
     * @return array<int|string, \Illuminate\Database\Eloquent\Collection<int, TRelatedModel>>
     */
    protected function addResultToDictionary(array $dictionary, Model $result, $pathSeparator, $path)
    {
        $keys = explode($pathSeparator, $path);

        if (!$this->andSelf) {
            array_pop($keys);
        }

        foreach ($keys as $key) {
            if (!isset($dictionary[$key])) {
                /** @var \Illuminate\Database\Eloquent\Collection<int, TRelatedModel> $emptyCollection */
                $emptyCollection = new Collection();

                $dictionary[$key] = $emptyCollection;
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
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<TDeclaringModel>
     */
    protected function getInitialQuery(ExpressionGrammar $grammar, callable $constraint, $alias, $selectPath)
    {
        $model = new $this->parent();

        $initialDepth = $this->andSelf ? 0 : 1;

        $depth = $grammar->wrap($model->getDepthName());

        /** @var \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<TDeclaringModel> $query */
        $query = $model->newModelQuery();

        /** @var string $from */
        $from = $query->getQuery()->from;

        $table = $alias ?: $from;

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
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<TDeclaringModel>
     */
    protected function getRecursiveQuery(ExpressionGrammar $grammar, $selectPath)
    {
        $model = new $this->parent();

        $name = $model->getExpressionName();

        $depth = $grammar->wrap($model->getDepthName());

        $recursiveDepth = "$depth + 1";

        /** @var \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<TDeclaringModel> $query */
        $query = $model->newModelQuery();

        /** @var string $from */
        $from = $query->getQuery()->from;

        $query->select("$from.*")
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
     * @param \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<TRelatedModel> $query
     * @param \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<TDeclaringModel> $parentQuery
     * @param list<string|\Illuminate\Database\Query\Expression>|string|\Illuminate\Database\Query\Expression $columns
     * @return \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<TRelatedModel>
     */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        $table = (new $this->parent())->getTable();

        if ($table === $parentQuery->getQuery()->from) {
            $table = $alias = $this->getRelationCountHash();
        } else {
            $alias = null;
        }

        $constraint = function (AdjacencyListBuilder $query) use ($table) {
            $this->addExistenceExpressionWhereConstraints($query, $table);
        };

        $query->select($columns);

        $this->addExpression($constraint, $query, $alias);

        return $query;
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
     * @return non-empty-string
     */
    protected function getPathListSeparator()
    {
        /** @var non-empty-string $pathSeparator */
        $pathSeparator = $this->parent->getPathSeparator();

        return str_repeat(
            $pathSeparator,
            2
        );
    }
}

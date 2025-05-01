<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Traits;

use Illuminate\Database\Eloquent\Builder;
use Staudenmeir\LaravelAdjacencyList\Query\Grammars\ExpressionGrammar;

/**
 * @phpstan-ignore trait.unused
 */
trait HasCycleDetection
{
    /**
     * Whether to enable cycle detection.
     *
     * @return bool
     */
    public function enableCycleDetection(): bool
    {
        return false;
    }

    /**
     * Whether to include the first row of the cycle in the query results.
     *
     * @return bool
     */
    public function includeCycleStart(): bool
    {
        return false;
    }

    /**
     * Get the name of the cycle detection column.
     *
     * @return string
     */
    public function getCycleDetectionColumnName(): string
    {
        return 'is_cycle';
    }

    /**
     * Add cycle detection to the initial query for a relationship expression.
     *
     * @param \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static> $query
     * @param \Staudenmeir\LaravelAdjacencyList\Query\Grammars\ExpressionGrammar $grammar
     * @return void
     */
    protected function addInitialQueryCycleDetection(Builder $query, ExpressionGrammar $grammar): void
    {
        if ($this->enableCycleDetection() && $this->includeCycleStart()) {
            $query->selectRaw(
                $grammar->compileCycleDetectionInitialSelect(
                    $this->getCycleDetectionColumnName()
                )
            );
        }
    }

    /**
     * Add cycle detection to the recursive query for a relationship expression.
     *
     * @param \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static> $query
     * @param \Staudenmeir\LaravelAdjacencyList\Query\Grammars\ExpressionGrammar $grammar
     * @return void
     */
    protected function addRecursiveQueryCycleDetection(Builder $query, ExpressionGrammar $grammar): void
    {
        if (!$this->enableCycleDetection()) {
            return;
        }

        $sql = $grammar->compileCycleDetection(
            $this->getQualifiedLocalKeyName(),
            $this->getPathName()
        );

        $bindings = $grammar->getCycleDetectionBindings(
            $this->getPathSeparator()
        );

        if ($this->includeCycleStart()) {
            $cycleDetectionColumn = $this->getCycleDetectionColumnName();

            $query->selectRaw(
                $grammar->compileCycleDetectionRecursiveSelect($sql, $cycleDetectionColumn),
                $bindings
            );

            $query->whereRaw(
                $grammar->compileCycleDetectionStopConstraint($cycleDetectionColumn)
            );
        } else {
            $query->whereRaw("not($sql)", $bindings);
        }
    }
}

<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @extends Descendants<TRelatedModel>
 */
class Bloodline extends Descendants
{
    /**
     * Create a new bloodline relationship instance.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model $parent
     * @param string $foreignKey
     * @param string $localKey
     * @return void
     */
    public function __construct(Builder $query, Model $parent, $foreignKey, $localKey)
    {
        parent::__construct($query, $parent, $foreignKey, $localKey, true);
    }

    /**
     * Add a recursive expression to the query.
     *
     * @param callable $constraint
     * @param \Illuminate\Database\Eloquent\Builder|null $query
     * @param string|null $from
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function addExpression(callable $constraint, Builder $query = null, $from = null)
    {
        $query = $query ?: $this->query;

        return $query->withRelationshipExpression('both', $constraint, 0, $from);
    }
}

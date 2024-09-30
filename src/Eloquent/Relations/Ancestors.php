<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Relations;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Staudenmeir\EloquentHasManyDeepContracts\Interfaces\ConcatenableRelation;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Traits\Concatenation\IsConcatenableAncestorsRelation;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Traits\IsAncestorRelation;

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends \Illuminate\Database\Eloquent\Relations\HasMany<TRelatedModel, TDeclaringModel>
 */
class Ancestors extends HasMany implements ConcatenableRelation
{
    /** @use \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Traits\IsAncestorRelation<TRelatedModel, TDeclaringModel> */
    use IsAncestorRelation;
    /** @use \Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Traits\Concatenation\IsConcatenableAncestorsRelation<TRelatedModel, TDeclaringModel> */
    use IsConcatenableAncestorsRelation;
}

<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;
use Staudenmeir\LaravelCte\Eloquent\QueriesExpressions;

class Role extends Model
{
    use HasRelationships;
    use QueriesExpressions;
    use SoftDeletes;

    public function userAncestors(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations(
            $this->users(),
            (new User())->ancestors()
        );
    }

    public function userBloodline(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations(
            $this->users(),
            (new User())->bloodline()
        );
    }

    public function userDescendants(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations(
            $this->users(),
            (new User())->descendants()
        );
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}

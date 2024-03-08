<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Expression;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasOneDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;
use Staudenmeir\EloquentHasManyDeep\HasTableAlias;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\BelongsToManyOfDescendants;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\HasManyOfDescendants;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\MorphToManyOfDescendants;

class User extends Model
{
    use HasRelationships;
    use HasRecursiveRelationships {
        getCustomPaths as baseGetCustomPaths;
    }
    use HasTableAlias;
    use SoftDeletes;

    public $incrementing = false;

    protected $casts = [
        'id' => 'int',
    ];

    public function getCustomPaths(): array
    {
        return array_merge(
            $this->baseGetCustomPaths(),
            [
                [
                    'name' => 'slug_path',
                    'column' => 'slug',
                    'separator' => '/',
                ],
                [
                    'name' => 'reverse_slug_path',
                    'column' => new Expression(
                        $this->newQuery()->getGrammar()->wrap('users.slug')
                    ),
                    'separator' => '/',
                    'reverse' => true,
                ],
            ]
        );
    }

    public function ancestorPost(): HasOneDeep
    {
        return $this->hasOneDeepFromRelations(
            $this->ancestors(),
            (new static())->hasMany(Post::class)
        );
    }

    public function ancestorPosts(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations(
            $this->ancestors(),
            (new static())->hasMany(Post::class)
        );
    }

    public function ancestorAndSelfPosts(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations(
            $this->ancestorsAndSelf(),
            (new static())->hasMany(Post::class)
        );
    }

    public function bloodlinePosts(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations(
            $this->bloodline(),
            (new static())->hasMany(Post::class)
        );
    }

    public function descendantPost(): HasOneDeep
    {
        return $this->hasOneDeepFromRelations(
            $this->descendants(),
            (new static())->hasMany(Post::class)
        );
    }

    public function descendantPosts(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations(
            $this->descendants(),
            (new static())->hasMany(Post::class)
        );
    }

    public function descendantPostsAndSelf(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations(
            $this->descendantsAndSelf(),
            (new static())->hasMany(Post::class)
        );
    }

    public function posts(): HasManyOfDescendants
    {
        return $this->hasManyOfDescendants(Post::class);
    }

    public function postsAndSelf(): HasManyOfDescendants
    {
        return $this->hasManyOfDescendantsAndSelf(Post::class);
    }

    public function roles(): BelongsToManyOfDescendants
    {
        return $this->belongsToManyOfDescendants(Role::class);
    }

    public function rolesAndSelf(): BelongsToManyOfDescendants
    {
        return $this->belongsToManyOfDescendantsAndSelf(Role::class);
    }

    public function tags(): MorphToManyOfDescendants
    {
        return $this->morphToManyOfDescendants(Tag::class, 'taggable');
    }

    public function tagsAndSelf(): MorphToManyOfDescendants
    {
        return $this->morphToManyOfDescendantsAndSelf(Tag::class, 'taggable');
    }

    public function videos(): MorphToManyOfDescendants
    {
        return $this->morphedByManyOfDescendants(Video::class, 'authorable');
    }

    public function videosAndSelf(): MorphToManyOfDescendants
    {
        return $this->morphedByManyOfDescendantsAndSelf(Video::class, 'authorable');
    }
}

<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

class User extends Model
{
    use HasRecursiveRelationships;
    use SoftDeletes;

    public function getCustomPaths()
    {
        return [
            [
                'name' => 'slug_path',
                'column' => 'slug',
                'separator' => '/',
            ],
        ];
    }

    public function posts()
    {
        return $this->hasManyOfDescendants(Post::class);
    }

    public function postsAndSelf()
    {
        return $this->hasManyOfDescendantsAndSelf(Post::class);
    }
}

<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

class Category extends Model
{
    use HasRecursiveRelationships;

    public $incrementing = false;

    protected $keyType = 'string';
}

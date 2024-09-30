<?php

namespace Staudenmeir\LaravelAdjacencyList\Types\Tree\Models;

use Illuminate\Database\Eloquent\Model;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

class User extends Model
{
    use HasRecursiveRelationships;
}

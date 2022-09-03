<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\IdeHelper\Models;

use Illuminate\Database\Eloquent\Model;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

class User extends Model
{
    use HasRecursiveRelationships;
}

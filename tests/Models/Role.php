<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Staudenmeir\LaravelCte\Eloquent\QueriesExpressions;

class Role extends Model
{
    use QueriesExpressions;
    use SoftDeletes;
}

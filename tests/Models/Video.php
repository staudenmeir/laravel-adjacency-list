<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Staudenmeir\LaravelCte\Eloquent\QueriesExpressions;

class Video extends Model
{
    use QueriesExpressions;
    use SoftDeletes;
}

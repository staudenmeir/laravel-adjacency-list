<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Staudenmeir\LaravelCte\Eloquent\QueriesExpressions;

/**
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Video extends Model
{
    use QueriesExpressions;
    use SoftDeletes;

    public $incrementing = false;
}

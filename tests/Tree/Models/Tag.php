<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Staudenmeir\LaravelCte\Eloquent\QueriesExpressions;

/**
 * @property Carbon|null $deleted_at
 */
class Tag extends Model
{
    use QueriesExpressions;
    use SoftDeletes;

    public $incrementing = false;
}

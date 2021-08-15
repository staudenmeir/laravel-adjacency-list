<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Staudenmeir\LaravelCte\Eloquent\QueriesExpressions;

class Post extends Model
{
    use QueriesExpressions;
    use SoftDeletes;
}

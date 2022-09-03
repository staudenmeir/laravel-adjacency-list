<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\IdeHelper\Models;

use Illuminate\Database\Eloquent\Model;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasGraphRelationships;

class Node extends Model
{
    use HasGraphRelationships;

    public function getPivotTableName(): string
    {
        return 'edges';
    }
}

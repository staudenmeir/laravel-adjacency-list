<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Graph\Models;

class NodeWithUuid extends Node
{
    public function getParentKeyName(): string
    {
        return 'parent_uuid';
    }

    public function getChildKeyName(): string
    {
        return 'child_uuid';
    }

    public function getLocalKeyName(): string
    {
        return 'uuid';
    }
}

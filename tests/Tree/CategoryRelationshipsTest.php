<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Tree;

use Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models\User;
use Mockery;

class CategoryRelationshipsTest extends TestCase
{
    public function testIsParentOf()
    {
        $parent = new User();
        $child = new User();

        $parent->setRelation('children', collect([$child]));

        $this->assertTrue($parent->isParentOf($child));
        $this->assertFalse($child->isParentOf($parent));
    }

    public function testIsChildOf()
    {
        $parent = new User();
        $child = new User();

        $child->setRelation('parent', $parent);

        $this->assertTrue($child->isChildOf($parent));
        $this->assertFalse($parent->isChildOf($child));
    }
}

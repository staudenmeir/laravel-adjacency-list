<?php

namespace Tests;

use Tests\Models\Category;

class PredicatesTest extends TestCase
{
    public function testIsRoot()
    {
        [$categoryA, $categoryB] = Category::findMany(['a', 'b']);

        $this->assertTrue($categoryA->isRoot());
        $this->assertFalse($categoryB->isRoot());
    }

    public function testIsChild()
    {
        [$categoryA, $categoryB] = Category::findMany(['a', 'b']);

        $this->assertFalse($categoryA->isChild());
        $this->assertTrue($categoryB->isChild());
    }

    public function testHasParent()
    {
        [$categoryA, $categoryB] = Category::findMany(['a', 'b']);

        $this->assertFalse($categoryA->hasParent());
        $this->assertTrue($categoryB->hasParent());
    }
}

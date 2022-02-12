<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent;

trait Predicates
{
    /**
     * Determine if the model has a parent
     *
     * @return bool
     */
    public function hasParent()
    {
        return $this->{$this->getParentKeyName()} !== null;
    }

    /**
     * Determine if the model is a child
     *
     * @return bool
     */
    public function isChild()
    {
        return $this->hasParent();
    }

    /**
     * Determine if the model is root
     *
     * @return bool
     */
    public function isRoot()
    {
        return $this->{$this->getParentKeyName()} === null;
    }
}
<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Relations\Traits;

use Illuminate\Database\Eloquent\SoftDeletingScope;

trait TracksIntermediateScopes
{
    /**
     * Applied intermediate scopes.
     *
     * @var array<string, \Closure|\Illuminate\Database\Eloquent\Scope>
     */
    protected $intermediateScopes = [];

    /**
     * Removed intermediate scopes.
     *
     * @var list<string|\Illuminate\Database\Eloquent\Scope>
     */
    protected $removedIntermediateScopes = [];

    /**
     * Register a new intermediate scope.
     *
     * @param string $identifier
     * @param \Closure|\Illuminate\Database\Eloquent\Scope $scope
     * @return $this
     */
    public function withIntermediateScope($identifier, $scope)
    {
        $this->intermediateScopes[$identifier] = $scope;

        if (method_exists($scope, 'extend')) {
            $scope->extend($this);
        }

        return $this;
    }

    /**
     * Remove a registered intermediate scope.
     *
     * @param string|\Illuminate\Database\Eloquent\Scope $scope
     * @return $this
     */
    public function withoutIntermediateScope($scope)
    {
        if (!is_string($scope)) {
            $scope = get_class($scope);
        }

        unset($this->intermediateScopes[$scope]);

        $this->removedIntermediateScopes[] = $scope;

        return $this;
    }

    /**
     * Remove all or passed registered intermediate scopes.
     *
     * @param list<string|\Illuminate\Database\Eloquent\Scope>|null $scopes
     * @return $this
     */
    public function withoutIntermediateScopes(?array $scopes = null)
    {
        if (!is_array($scopes)) {
            $scopes = array_keys($this->intermediateScopes);
        }

        foreach ($scopes as $scope) {
            $this->withoutIntermediateScope($scope);
        }

        return $this;
    }

    /**
     * Include trashed descendants in the query.
     *
     * @return $this
     */
    public function withTrashedDescendants()
    {
        return $this->withoutIntermediateScope(SoftDeletingScope::class);
    }

    /**
     * Get applied intermediate scopes.
     *
     * @return array<string, \Closure|\Illuminate\Database\Eloquent\Scope>
     */
    public function intermediateScopes()
    {
        return $this->intermediateScopes;
    }

    /**
     * Get removed intermediate scopes.
     *
     * @return list<string|\Illuminate\Database\Eloquent\Scope>
     */
    public function removedIntermediateScopes()
    {
        return $this->removedIntermediateScopes;
    }
}

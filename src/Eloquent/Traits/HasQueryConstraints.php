<?php

namespace Staudenmeir\LaravelAdjacencyList\Eloquent\Traits;

trait HasQueryConstraints
{
    /**
     * The additional constraint for the initial query.
     *
     * @var callable|null
     */
    public static $initialQueryConstraint;

    /**
     * The additional constraint for the recursive query.
     *
     * @var callable|null
     */
    public static $recursiveQueryConstraint;

    /**
     * Execute a query with an additional constraint for the initial query.
     *
     * @param callable $constraint
     * @param callable $query
     * @return mixed
     */
    public static function withInitialQueryConstraint(callable $constraint, callable $query): mixed
    {
        $previous = static::$initialQueryConstraint;

        static::$initialQueryConstraint = $constraint;

        $result = $query();

        static::$initialQueryConstraint = $previous;

        return $result;
    }

    /**
     * Execute a query with an additional constraint for the recursive query.
     *
     * @param callable $constraint
     * @param callable $query
     * @return mixed
     */
    public static function withRecursiveQueryConstraint(callable $constraint, callable $query): mixed
    {
        $previous = static::$recursiveQueryConstraint;

        static::$recursiveQueryConstraint = $constraint;

        $result = $query();

        static::$recursiveQueryConstraint = $previous;

        return $result;
    }

    /**
     * Set an additional constraint for the recursive query.
     *
     * @param callable $constraint
     * @return void
     */
    public static function setRecursiveQueryConstraint(callable $constraint): void
    {
        static::$recursiveQueryConstraint = $constraint;
    }

    /**
     * Unset the additional constraint for the recursive query.
     *
     * @return void
     */
    public static function unsetRecursiveQueryConstraint(): void
    {
        static::$recursiveQueryConstraint = null;
    }

    /**
     * Execute a query with an additional constraint for the initial and recursive query.
     *
     * @param callable $constraint
     * @param callable $query
     * @return mixed
     */
    public static function withQueryConstraint(
        callable $constraint,
        callable $query
    ): mixed {
        $previousInitialConstraint = static::$initialQueryConstraint;
        $previousRecursiveConstraint = static::$recursiveQueryConstraint;

        static::$initialQueryConstraint = $constraint;
        static::$recursiveQueryConstraint = $constraint;

        $result = $query();

        static::$initialQueryConstraint = $previousInitialConstraint;
        static::$recursiveQueryConstraint = $previousRecursiveConstraint;

        return $result;
    }
}

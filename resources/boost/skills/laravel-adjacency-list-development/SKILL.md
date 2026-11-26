---
name: laravel-adjacency-list-development
description: Build and work with staudenmeir/laravel-adjacency-list tree and graph features, including recursive Eloquent relationships, CTE query scopes, hierarchy ordering and depth, custom recursive constraints, and cycle-safe traversal.
license: MIT
metadata:
  author: Jonas Staudenmeir
---

# Laravel Adjacency List Development

Use this skill when working with hierarchical or graph data through
`staudenmeir/laravel-adjacency-list`.

## When to Activate

- Activate for models using `HasRecursiveRelationships` or `HasGraphRelationships`.
- Activate for ancestor, descendant, parent, child, sibling, bloodline, or root queries.
- Activate for recursive relationship methods, custom descendant relationships, cycle detection, depth, path, or tree scopes.
- Activate for graph edges stored in a pivot table or for concatenated graph relationships.

## Choose the Correct Model Trait

Use `HasRecursiveRelationships` for an ordinary one-to-many hierarchy with a
`parent_id` column:

```php
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

class Category extends Model
{
    use HasRecursiveRelationships;
}
```

Use `HasGraphRelationships` for many-to-many adjacency where nodes are connected
through a pivot table:

```php
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasGraphRelationships;

class Node extends Model
{
    use HasGraphRelationships;

    public function getPivotTableName(): string
    {
        return 'edges';
    }
}
```

Do not model a graph with `HasRecursiveRelationships`; graph traversal requires
`HasGraphRelationships` and pivot key configuration.

## Key Overrides

Tree models may customize the parent and local keys:

```php
public function getParentKeyName(): string
{
    return 'parent_id';
}

public function getLocalKeyName(): string
{
    return 'id';
}
```

Graph models also customize pivot columns:

```php
public function getPivotTableName(): string
{
    return 'edges';
}

public function getParentKeyName(): string
{
    return 'source_id';
}

public function getChildKeyName(): string
{
    return 'target_id';
}
```

Keep key names consistent with migrations and pivot `select`/`where` constraints.

## Tree Relationships

`HasRecursiveRelationships` provides these common relationships:

- `parent()` and `parentAndSelf()`
- `children()` and `childrenAndSelf()`
- `ancestors()` and `ancestorsAndSelf()`
- `descendants()` and `descendantsAndSelf()`
- `bloodline()`
- `rootAncestor()` and `rootAncestorOrSelf()`
- `siblings()` and `siblingsAndSelf()`

Use relationship methods with normal Eloquent constraints:

```php
$ancestors = Category::findOrFail($id)->ancestors;
$descendants = Category::findOrFail($id)->descendants()->where('active', true)->get();
$count = Category::findOrFail($id)->descendants()->count();
```

Use `tree()` for roots and their complete hierarchy, and `treeOf()` for
application-specific roots:

```php
$tree = Category::tree()->get();

$tree = Category::treeOf(function ($query) {
    $query->whereNull('parent_id')->where('type', 'navigation');
})->get();

$tree = Category::tree(3)->get();
```

## Graph Relationships

`HasGraphRelationships` provides recursive `parents()`, `parentsAndSelf()`,
`children()`, `childrenAndSelf()`, `ancestors()`, `ancestorsAndSelf()`,
`descendants()`, and `descendantsAndSelf()` relationships.

```php
$node = Node::findOrFail($id);

$ancestors = $node->ancestors;
$descendants = $node->descendants()->where('active', true)->get();
```

Graph traversal can find the same node through multiple paths. Choose and test
whether expected results are unique when eager loading or collecting results.

## Depth, Path, and Ordering

Use package scopes and columns consistently where supported:

- `whereDepth()` filters a result's relative depth.
- `withMaxDepth()` limits CTE recursion for better query performance.
- `breadthFirst()` and `depthFirst()` control result ordering.
- The result adds `depth` and `path` columns for ancestor, descendant, tree, and subgraph queries.

Override `getDepthName()` or `getPathName()` when a model already uses a
column with one of those names.

`withMaxDepth()` is a static scope callback helper in this package. The package
README shows the supported form:

```php
$descendants = Category::withMaxDepth(3, function () use ($id) {
    return Category::findOrFail($id)->descendants();
});
```

Prefer a package-native scope over manually rewriting recursive CTEs.

For custom ordering or output shape, inspect the database grammar implementation
and test against every supported database. CTE syntax and pivot/column quoting
vary by driver.

## Initial and Recursive Constraints

Keep constraints on the initial and recursive portions of a traversal separate
when behavior differs:

Read the package relationship implementation before changing a custom
relationship so a constraint is applied to the intended CTE part.

## Custom Descendant Relationships

For a regular Eloquent relation reached through descendants, use the package's
custom relationship classes and package-specific `andSelf()` variants:

- `hasManyOfDescendants()` / `hasManyOfDescendantsAndSelf()`
- `belongsToManyOfDescendants()` / `belongsToManyOfDescendantsAndSelf()`
- `morphedByManyOfDescendants()` / `morphedByManyOfDescendantsAndSelf()`
- `morphToManyOfDescendants()` / `morphToManyOfDescendantsAndSelf()`

Preserve the existing return type and relationship signature. Do not replace a
package relationship with raw recursive SQL unless the package cannot represent
the required behavior.

## Cycle Detection

Cycles are meaningful in graph data and invalid or unsafe in tree data. Enable
the package's cycle detection for tree traversals that may encounter corrupted
or user-editable parent links.

Add or update tests that prove a cycle cannot produce an infinite CTE traversal
and that direct parent/child mutations are rejected or safely handled.

## Verification

Add focused PHPUnit coverage under `tests/Tree/` or `tests/Graph/`:

- Verify direct and nested relationships.
- Verify eager loading and where/count/update/delete operations.
- Verify custom key and pivot names.
- Verify depth limits, depth/path values, and ordering.
- Verify initial and recursive constraints.
- Verify cycle handling for trees.
- Run the smallest relevant test class first, then the full suite and static
  analysis when the change affects public behavior.

```bash
vendor/bin/phpunit tests/Tree/DescendantsTest.php
vendor/bin/phpunit
vendor/bin/phpstan analyse
```

## Common Pitfalls

- Do not assume every Eloquent method works the same for a recursive CTE relation.
- Do not set `parent_id` to itself or a descendant in tree mutations without cycle checks.
- Do not use graph pivot column names as model columns.
- Do not configure depth/path columns without selecting or retaining required model keys.
- Do not change SQL grammars or CTE compilation when a relationship/scopes API is sufficient.
- Test all database grammars affected by a query change; string SQL can differ across MySQL, MariaDB, PostgreSQL, SQLite, SQL Server, and SingleStore.

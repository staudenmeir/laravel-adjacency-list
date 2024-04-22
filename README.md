# Laravel Adjacency List

[![CI](https://github.com/staudenmeir/laravel-adjacency-list/actions/workflows/ci.yml/badge.svg)](https://github.com/staudenmeir/laravel-adjacency-list/actions/workflows/ci.yml)
[![Code Coverage](https://codecov.io/gh/staudenmeir/laravel-adjacency-list/graph/badge.svg?token=VhZ3oBh1YE)](https://codecov.io/gh/staudenmeir/laravel-adjacency-list)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/staudenmeir/laravel-adjacency-list/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/staudenmeir/laravel-adjacency-list/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/staudenmeir/laravel-adjacency-list/v/stable)](https://packagist.org/packages/staudenmeir/laravel-adjacency-list)
[![Total Downloads](https://poser.pugx.org/staudenmeir/laravel-adjacency-list/downloads)](https://packagist.org/packages/staudenmeir/laravel-adjacency-list/stats)
[![License](https://poser.pugx.org/staudenmeir/laravel-adjacency-list/license)](https://github.com/staudenmeir/laravel-adjacency-list/blob/master/LICENSE)

This Laravel Eloquent extension provides recursive relationships for [trees](#trees-one-parent-per-node-one-to-many) and
[graphs](#graphs-multiple-parents-per-node-many-to-many) using common table expressions (CTE).

## Compatibility

- MySQL 8.0+
- MariaDB 10.2+
- PostgreSQL 9.4+
- SQLite 3.8.3+
- SQL Server 2008+
- SingleStore 8.1+ (only [trees](#trees-one-parent-per-node-one-to-many))
- Firebird

## Installation

    composer require staudenmeir/laravel-adjacency-list:"^1.0"

Use this command if you are in PowerShell on Windows (e.g. in VS Code):

    composer require staudenmeir/laravel-adjacency-list:"^^^^1.0"

## Versions

| Laravel | Package |
|:--------|:--------|
| 11.x    | 1.21    |
| 10.x    | 1.13    |
| 9.x     | 1.12    |
| 8.x     | 1.9     |
| 7.x     | 1.5     |
| 6.x     | 1.3     |
| 5.8     | 1.1     |
| 5.5–5.7 | 1.0     |

## Usage

The package offers recursive relationships for traversing two types of data structures:

- [Trees: One Parent per Node (One-to-Many)](#trees-one-parent-per-node-one-to-many)
- [Graphs: Multiple Parents per Node (Many-to-Many)](#graphs-multiple-parents-per-node-many-to-many)

### Trees: One Parent per Node (One-to-Many)

Use the package to traverse a tree structure with one parent per node. Use cases might be recursive categories, a page
hierarchy or nested comments.

Supports Laravel 5.5+.

- [Getting Started](#getting-started)
- [Included Relationships](#included-relationships)
- [Trees](#trees)
- [Filters](#filters)
- [Order](#order)
- [Depth](#depth)
- [Path](#path)
- [Custom Paths](#custom-paths)
- [Nested Results](#nested-results)
- [Initial & Recursive Query Constraints](#initial--recursive-query-constraints)
- [Additional Methods](#additional-methods)
- [Custom Relationships](#custom-relationships)
- [Deep Relationship Concatenation](#deep-relationship-concatenation)
- [Known Issues](#known-issues)

#### Getting Started

Consider the following table schema for hierarchical data in trees:

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('parent_id')->nullable();
});
```

Use the `HasRecursiveRelationships` trait in your model to work with recursive relationships:

```php
class User extends Model
{
    use \Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;
}
```

By default, the trait expects a parent key named `parent_id`. You can customize it by overriding `getParentKeyName()`:

```php
class User extends Model
{
    use \Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;
    
    public function getParentKeyName()
    {
        return 'parent_id';
    }
}
```

By default, the trait uses the model's primary key as the local key. You can customize it by
overriding `getLocalKeyName()`:

```php
class User extends Model
{
    use \Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;
    
    public function getLocalKeyName()
    {
        return 'id';
    }
}
```

#### Included Relationships

The trait provides various relationships:

- `ancestors()`: The model's recursive parents.
- `ancestorsAndSelf()`: The model's recursive parents and itself.
- `bloodline()`: The model's ancestors, descendants and itself.
- `children()`: The model's direct children.
- `childrenAndSelf()`: The model's direct children and itself.
- `descendants()`: The model's recursive children.
- `descendantsAndSelf()`: The model's recursive children and itself.
- `parent()`: The model's direct parent.
- `parentAndSelf()`: The model's direct parent and itself.
- `rootAncestor()`: The model's topmost parent.
- `rootAncestorOrSelf()`: The model's topmost parent or itself.
- `siblings()`: The parent's other children.
- `siblingsAndSelf()`: All the parent's children.

```php
$ancestors = User::find($id)->ancestors;

$users = User::with('descendants')->get();

$users = User::whereHas('siblings', function ($query) {
    $query->where('name', 'John');
})->get();

$total = User::find($id)->descendants()->count();

User::find($id)->descendants()->update(['active' => false]);

User::find($id)->siblings()->delete();
```

#### Trees

The trait provides the `tree()` query scope to get all models, beginning at the root(s):

```php
$tree = User::tree()->get();
```

`treeOf()` allows you to query trees with custom constraints for the root model(s). Consider a table with multiple
separate lists:

```php
$constraint = function ($query) {
    $query->whereNull('parent_id')->where('list_id', 1);
};

$tree = User::treeOf($constraint)->get();
```

You can also pass a maximum depth:

```php
$tree = User::tree(3)->get();

$tree = User::treeOf($constraint, 3)->get();
```

#### Filters

The trait provides query scopes to filter models by their position in the tree:

- `hasChildren()`: Models with children.
- `hasParent()`: Models with a parent.
- `isLeaf()`/`doesntHaveChildren()`: Models without children.
- `isRoot()`: Models without a parent.

```php
$noLeaves = User::hasChildren()->get();

$noRoots = User::hasParent()->get();

$leaves = User::isLeaf()->get();
$leaves = User::doesntHaveChildren()->get();

$roots = User::isRoot()->get();
```

#### Order

The trait provides query scopes to order models breadth-first or depth-first:

- `breadthFirst()`: Get siblings before children.
- `depthFirst()`: Get children before siblings.

```php
$tree = User::tree()->breadthFirst()->get();

$descendants = User::find($id)->descendants()->depthFirst()->get();
```

#### Depth

The results of ancestor, bloodline, descendant and tree queries include an additional `depth` column.

It contains the model's depth *relative* to the query's parent. The depth is positive for descendants and negative for
ancestors:

```php
$descendantsAndSelf = User::find($id)->descendantsAndSelf()->depthFirst()->get();

echo $descendantsAndSelf[0]->depth; // 0
echo $descendantsAndSelf[1]->depth; // 1
echo $descendantsAndSelf[2]->depth; // 2
```

You can customize the column name by overriding `getDepthName()`:

```php
class User extends Model
{
    use \Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

    public function getDepthName()
    {
        return 'depth';
    }
}
```

##### Depth Constraints

You can use the `whereDepth()` query scope to filter models by their relative depth:

```php
$descendants = User::find($id)->descendants()->whereDepth(2)->get();

$descendants = User::find($id)->descendants()->whereDepth('<', 3)->get();
```

Queries with `whereDepth()` constraints that limit the maximum depth still build the entire (sub)tree internally.
Use `withMaxDepth()` to set a maximum depth that improves query performance by only building the requested section of
the tree:

```php
$descendants = User::withMaxDepth(3, function () use ($id) {
    return User::find($id)->descendants;
});
```

This also works with negative depths (where it's technically a minimum):

```php
$ancestors = User::withMaxDepth(-3, function () use ($id) {
    return User::find($id)->ancestors;
});
```

#### Path

The results of ancestor, bloodline, descendant and tree queries include an additional `path` column.

It contains the dot-separated path of local keys from the query's parent to the model:

```php
$descendantsAndSelf = User::find(1)->descendantsAndSelf()->depthFirst()->get();

echo $descendantsAndSelf[0]->path; // 1
echo $descendantsAndSelf[1]->path; // 1.2
echo $descendantsAndSelf[2]->path; // 1.2.3
```

You can customize the column name and the separator by overriding the respective methods:

```php
class User extends Model
{
    use \Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

    public function getPathName()
    {
        return 'path';
    }

    public function getPathSeparator()
    {
        return '.';
    }
}
```

#### Custom Paths

You can add custom path columns to the query results:

```php
class User extends Model
{
    use \Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

    public function getCustomPaths()
    {
        return [
            [
                'name' => 'slug_path',
                'column' => 'slug',
                'separator' => '/',
            ],
        ];
    }
}

$descendantsAndSelf = User::find(1)->descendantsAndSelf;

echo $descendantsAndSelf[0]->slug_path; // user-1
echo $descendantsAndSelf[1]->slug_path; // user-1/user-2
echo $descendantsAndSelf[2]->slug_path; // user-1/user-2/user-3
```

You can also reverse custom paths:

```php
class User extends Model
{
    use \Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

    public function getCustomPaths()
    {
        return [
            [
                'name' => 'reverse_slug_path',
                'column' => 'slug',
                'separator' => '/',
                'reverse' => true,
            ],
        ];
    }
}
```

#### Nested Results

Use the `toTree()` method on a result collection to generate a nested tree:

```php
$users = User::tree()->get();

$tree = $users->toTree();
```

This recursively sets `children` relationships:

```json
[
  {
    "id": 1,
    "children": [
      {
        "id": 2,
        "children": [
          {
            "id": 3,
            "children": []
          }
        ]
      },
      {
        "id": 4,
        "children": [
          {
            "id": 5,
            "children": []
          }
        ]
      }
    ]
  }
]
```

#### Initial & Recursive Query Constraints

You can add custom constraints to the CTE's initial and recursive query. Consider a query where you want to traverse a
tree while skipping inactive users and their descendants:

 ```php
$tree = User::withQueryConstraint(function (Builder $query) {
    $query->where('users.active', true);
}, function () {
    return User::tree()->get();
});
 ```

 You can also add a custom constraint to only the initial or recursive query using `withInitialQueryConstraint()`/
 `withRecursiveQueryConstraint()`.

#### Additional Methods

The trait also provides methods to check relationships between models:

- `isChildOf(Model $model)`: Checks if the current model is a child of the given model.
- `isParentOf(Model $model)`: Checks if the current model is a parent of the given model.
- `getDepthRelatedTo(Model $model)`: Returns the depth of the current model related to the given model.

```php
$rootUser = User::create(['parent_id' => null]); 
$firstLevelUser = User::create(['parent_id' => $rootUser->id]); 
$secondLevelUser = User::create(['parent_id' => $firstLevelUser->id]);  

$isChildOf = $secondLevelUser->isChildOf($firstLevelUser); // Output: true
$isParentOf = $rootUser->isParentOf($firstLevelUser); // Output: true
$depthRelatedTo = $secondLevelUser->getDepthRelatedTo($rootUser); // Output: 2
```

#### Custom Relationships

You can also define custom relationships to retrieve related models recursively.

- [HasManyOfDescendants](#hasmanyofdescendants)
- [BelongsToManyOfDescendants](#belongstomanyofdescendants)
- [MorphToManyOfDescendants](#morphtomanyofdescendants)
- [MorphedByManyOfDescendants](#morphedbymanyofdescendants)
- [Intermediate Scopes](#intermediate-scopes)
- [Usage outside of Laravel](#usage-outside-of-laravel)

##### HasManyOfDescendants

Consider a `HasMany` relationship between `User` and `Post`:

 ```php
 class User extends Model
 {
     public function posts()
     {
         return $this->hasMany(Post::class);
     }
 }
 ```

Define a `HasManyOfDescendants` relationship to get all posts of a user and its descendants:

```php
class User extends Model
{
    use \Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

    public function recursivePosts()
    {
        return $this->hasManyOfDescendantsAndSelf(Post::class);
    }
}

$recursivePosts = User::find($id)->recursivePosts;

$users = User::withCount('recursivePosts')->get();
```

Use `hasManyOfDescendants()` to only get the descendants' posts:

```php
class User extends Model
{
    use \Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

    public function descendantPosts()
    {
        return $this->hasManyOfDescendants(Post::class);
    }
}
```

##### BelongsToManyOfDescendants

Consider a `BelongsToMany` relationship between `User` and `Role`:

 ```php
 class User extends Model
 {
     public function roles()
     {
         return $this->belongsToMany(Role::class);
     }
 }
 ```

Define a `BelongsToManyOfDescendants` relationship to get all roles of a user and its descendants:

```php
class User extends Model
{
    use \Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

    public function recursiveRoles()
    {
        return $this->belongsToManyOfDescendantsAndSelf(Role::class);
    }
}

$recursiveRoles = User::find($id)->recursiveRoles;

$users = User::withCount('recursiveRoles')->get();
```

Use `belongsToManyOfDescendants()` to only get the descendants' roles:

```php
class User extends Model
{
    use \Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

    public function descendantRoles()
    {
        return $this->belongsToManyOfDescendants(Role::class);
    }
}
```

##### MorphToManyOfDescendants

Consider a `MorphToMany` relationship between `User` and `Tag`:

 ```php
 class User extends Model
 {
     public function tags()
     {
         return $this->morphToMany(Tag::class, 'taggable');
     }
 }
 ```

Define a `MorphToManyOfDescendants` relationship to get all tags of a user and its descendants:

```php
class User extends Model
{
    use \Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

    public function recursiveTags()
    {
        return $this->morphToManyOfDescendantsAndSelf(Tag::class, 'taggable');
    }
}

$recursiveTags = User::find($id)->recursiveTags;

$users = User::withCount('recursiveTags')->get();
```

Use `morphToManyOfDescendants()` to only get the descendants' tags:

```php
class User extends Model
{
    use \Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

    public function descendantTags()
    {
        return $this->morphToManyOfDescendants(Tag::class, 'taggable');
    }
}
```

##### MorphedByManyOfDescendants

Consider a `MorphedByMany` relationship between `Category` and `Post`:

 ```php
 class Category extends Model
 {
     public function posts()
     {
         return $this->morphedByMany(Post::class, 'categorizable');
     }
 }
 ```

Define a `MorphedByManyOfDescendants` relationship to get all posts of a category and its descendants:

```php
class Category extends Model
{
    use \Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

    public function recursivePosts()
    {
        return $this->morphedByManyOfDescendantsAndSelf(Post::class, 'categorizable');
    }
}

$recursivePosts = Category::find($id)->recursivePosts;

$categories = Category::withCount('recursivePosts')->get();
```

Use `morphedByManyOfDescendants()` to only get the descendants' posts:

```php
class Category extends Model
{
    use \Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

    public function descendantPosts()
    {
        return $this->morphedByManyOfDescendants(Post::class, 'categorizable');
    }
}
```

##### Intermediate Scopes

You can adjust the descendants query (e.g. child users) by adding or removing intermediate scopes:

```php
User::find($id)->recursivePosts()->withTrashedDescendants()->get();

User::find($id)->recursivePosts()->withIntermediateScope('active', new ActiveScope())->get();

User::find($id)->recursivePosts()->withIntermediateScope(
    'depth',
    function ($query) {
        $query->whereDepth('<=', 10);
    }
)->get();

User::find($id)->recursivePosts()->withoutIntermediateScope('active')->get();
```

##### Usage outside of Laravel

If you are using the package outside of Laravel or have disabled package discovery for `staudenmeir/laravel-cte`, you
need to add support for common table expressions to the related model:

```php
class Post extends Model
{
    use \Staudenmeir\LaravelCte\Eloquent\QueriesExpressions;
}
```

#### Deep Relationship Concatenation

You can include recursive relationships into deep relationships by concatenating them with other relationships
using [staudenmeir/eloquent-has-many-deep](https://github.com/staudenmeir/eloquent-has-many-deep). This
works with `Ancestors`, `Bloodline` and `Descendants` relationships (Laravel 9+).

Consider a `HasMany` relationship between `User` and `Post` and building a deep relationship to get all posts of a
user's descendants:

`User` → descendants → `User` → has many → `Post`

[Install](https://github.com/staudenmeir/eloquent-has-many-deep/#installation) the additional package, add the
`HasRelationships` trait to the recursive model
and [define](https://github.com/staudenmeir/eloquent-has-many-deep/#concatenating-existing-relationships) a
deep relationship:

```php
class User extends Model
{
    use \Staudenmeir\EloquentHasManyDeep\HasRelationships;
    use \Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

    public function descendantPosts()
    {
        return $this->hasManyDeepFromRelations(
            $this->descendants(),
            (new static)->posts()
        );
    }
    
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}

$descendantPosts = User::find($id)->descendantPosts;
```

At the moment, recursive relationships can only be at the beginning of deep relationships:

- Supported: `User` → descendants → `User` → has many → `Post`
- Not supported: `Post` → belongs to → `User` → descendants → `User`

#### Known Issues

MariaDB [doesn't yet support](https://jira.mariadb.org/browse/MDEV-19077) correlated CTEs in subqueries. This affects
queries like `User::whereHas('descendants')` or `User::withCount('descendants')`.

### Graphs: Multiple Parents per Node (Many-to-Many)

You can also use the package to traverse graphs with multiple parents per node that are defined in a pivot table. Use
cases might be a bill of materials (BOM) or a family tree.

Supports Laravel 9+.

- [Getting Started](#graphs-getting-started)
- [Included Relationships](#graphs-included-relationships)
- [Pivot Columns](#graphs-pivot-columns)
- [Cycle Detection](#graphs-cycle-detection)
- [Subgraphs](#graphs-subgraphs)
- [Order](#graphs-order)
- [Depth](#graphs-depth)
- [Path](#graphs-path)
- [Custom Paths](#graphs-custom-paths)
- [Nested Results](#graphs-nested-results)
- [Initial & Recursive Query Constraints](#graphs-initial--recursive-query-constraints)
- [Deep Relationship Concatenation](#graphs-deep-relationship-concatenation)
- [Known Issues](#graphs-known-issues)

#### <a name="graphs-getting-started">Getting Started</a>

Consider the following table schema for storing directed graphs as nodes and edges:

```php
Schema::create('nodes', function (Blueprint $table) {
    $table->id();
});

Schema::create('edges', function (Blueprint $table) {
    $table->unsignedBigInteger('source_id');
    $table->unsignedBigInteger('target_id');
    $table->string('label');
    $table->unsignedBigInteger('weight');
});
```

Use the `HasGraphRelationships` trait in your model to work with graph relationships and specify the name of the pivot
table:

```php
class Node extends Model
{
    use \Staudenmeir\LaravelAdjacencyList\Eloquent\HasGraphRelationships;

    public function getPivotTableName(): string
    {
        return 'edges';
    }
}
```

By default, the trait expects a parent key named `parent_id` and child key named `child_id` in the pivot table. You can
customize them by overriding `getParentKeyName()` and `getChildKeyName()`:

```php
class Node extends Model
{
    public function getParentKeyName(): string
    {
        return 'source_id';
    }
  
    public function getChildKeyName(): string
    {
        return 'target_id';
    }
}
```

By default, the trait uses the model's primary key as the local key. You can customize it by
overriding `getLocalKeyName()`:

```php
class Node extends Model
{
    public function getLocalKeyName(): string
    {
        return 'id';
    }
}
```

#### <a name="graphs-included-relationships">Included Relationships</a>

The trait provides various relationships:

- `ancestors()`: The node's recursive parents.
- `ancestorsAndSelf()`: The node's recursive parents and itself.
- `children()`: The node's direct children.
- `childrenAndSelf()`: The node's direct children and itself.
- `descendants()`: The node's recursive children.
- `descendantsAndSelf()`: The node's recursive children and itself.
- `parents()`: The node's direct parents.
- `parentsAndSelf()`: The node's direct parents and itself.

```php
$ancestors = Node::find($id)->ancestors;

$nodes = Node::with('descendants')->get();

$nodes = Node::has('children')->get();

$total = Node::find($id)->descendants()->count();

Node::find($id)->descendants()->update(['active' => false]);

Node::find($id)->parents()->delete();
```

#### <a name="graphs-pivot-columns">Pivot Columns</a>

Similar to `BelongsToMany` relationships, you can retrieve additional columns from the pivot table besides the parent
and child key:

```php
class Node extends Model
{
    public function getPivotColumns(): array
    {
        return ['label', 'weight'];
    }
}

$nodes = Node::find($id)->descendants;

foreach ($nodes as $node) {
    dump(
        $node->pivot->label,
        $node->pivot->weight
    );
}
```

#### <a name="graphs-cycle-detection">Cycle Detection

If your graph contains cycles, you need to enable cycle detection to prevent infinite loops:

```php
class Node extends Model
{
    public function enableCycleDetection(): bool
    {
        return true;
    }
}
```

You can also retrieve the start of a cycle, i.e. the first duplicate node. With this option, the query results include
an `is_cycle` column that indicates whether the node is part of a cycle:

```php
class Node extends Model
{
    public function enableCycleDetection(): bool
    {
        return true;
    }

    public function includeCycleStart(): bool
    {
        return true;
    }
}

$nodes = Node::find($id)->descendants;

foreach ($nodes as $node) {
    dump($node->is_cycle);
}
```

#### <a name="graphs-subgraphs">Subgraphs</a>

The trait provides the `subgraph()` query scope to get the subgraph of a custom constraint:

```php
$constraint = function ($query) {
    $query->whereIn('id', $ids);
};

$subgraph = Node::subgraph($constraint)->get();
```

You can pass a maximum depth as the second argument:

```php
$subgraph = Node::subgraph($constraint, 3)->get();
```

#### <a name="graphs-order">Order</a>

The trait provides query scopes to order nodes breadth-first or depth-first:

- `breadthFirst()`: Get siblings before children.
- `depthFirst()`: Get children before siblings.

```php
$descendants = Node::find($id)->descendants()->breadthFirst()->get();

$descendants = Node::find($id)->descendants()->depthFirst()->get();
```

#### <a name="graphs-depth">Depth</a>

The results of ancestor, descendant and subgraph queries include an additional `depth` column.

It contains the node's depth *relative* to the query's parent. The depth is positive for descendants and negative for
ancestors:

```php
$descendantsAndSelf = Node::find($id)->descendantsAndSelf()->depthFirst()->get();

echo $descendantsAndSelf[0]->depth; // 0
echo $descendantsAndSelf[1]->depth; // 1
echo $descendantsAndSelf[2]->depth; // 2
```

You can customize the column name by overriding `getDepthName()`:

```php
class Node extends Model
{
    public function getDepthName(): string
    {
        return 'depth';
    }
}
```

##### Depth Constraints

You can use the `whereDepth()` query scope to filter nodes by their relative depth:

```php
$descendants = Node::find($id)->descendants()->whereDepth(2)->get();

$descendants = Node::find($id)->descendants()->whereDepth('<', 3)->get();
```

Queries with `whereDepth()` constraints that limit the maximum depth still build the entire (sub)graph internally.
Use `withMaxDepth()` to set a maximum depth that improves query performance by only building the requested section of
the graph:

```php
$descendants = Node::withMaxDepth(3, function () use ($id) {
    return Node::find($id)->descendants;
});
```

This also works with negative depths (where it's technically a minimum):

```php
$ancestors = Node::withMaxDepth(-3, function () use ($id) {
    return Node::find($id)->ancestors;
});
```

#### <a name="graphs-path">Path</a>

The results of ancestor, descendant and subgraph queries include an additional `path` column.

It contains the dot-separated path of local keys from the query's parent to the node:

```php
$descendantsAndSelf = Node::find(1)->descendantsAndSelf()->depthFirst()->get();

echo $descendantsAndSelf[0]->path; // 1
echo $descendantsAndSelf[1]->path; // 1.2
echo $descendantsAndSelf[2]->path; // 1.2.3
```

You can customize the column name and the separator by overriding the respective methods:

```php
class Node extends Model
{
    public function getPathName(): string
    {
        return 'path';
    }

    public function getPathSeparator(): string
    {
        return '.';
    }
}
```

#### <a name="graphs-custom-paths">Custom Paths</a>

You can add custom path columns to the query results:

```php
class Node extends Model
{
    public function getCustomPaths(): array
    {
        return [
            [
                'name' => 'slug_path',
                'column' => 'slug',
                'separator' => '/',
            ],
        ];
    }
}

$descendantsAndSelf = Node::find(1)->descendantsAndSelf;

echo $descendantsAndSelf[0]->slug_path; // node-1
echo $descendantsAndSelf[1]->slug_path; // node-1/node-2
echo $descendantsAndSelf[2]->slug_path; // node-1/node-2/node-3
```

You can also reverse custom paths:

```php
class Node extends Model
{
    public function getCustomPaths(): array
    {
        return [
            [
                'name' => 'reverse_slug_path',
                'column' => 'slug',
                'separator' => '/',
                'reverse' => true,
            ],
        ];
    }
}
```

#### <a name="graphs-nested-results">Nested Results</a>

Use the `toTree()` method on a result collection to generate a nested tree:

```php
$nodes = Node::find($id)->descendants;

$tree = $nodes->toTree();
```

This recursively sets `children` relationships:

```json
[
  {
    "id": 1,
    "children": [
      {
        "id": 2,
        "children": [
          {
            "id": 3,
            "children": []
          }
        ]
      },
      {
        "id": 4,
        "children": [
          {
            "id": 5,
            "children": []
          }
        ]
      }
    ]
  }
]
```

#### <a name="graphs-initial--recursive-query-constraints">Initial & Recursive Query Constraints</a>

You can add custom constraints to the CTE's initial and recursive query. Consider a query where you want to traverse a
node's descendants while skipping inactive nodes and their descendants:

 ```php
$descendants = Node::withQueryConstraint(function (Builder $query) {
    $query->where('nodes.active', true);
}, function () {
    return Node::find($id)->descendants;
});
 ```

You can also add a custom constraint to only the initial or recursive query using `withInitialQueryConstraint()`/
`withRecursiveQueryConstraint()`.

#### <a name="graphs-deep-relationship-concatenation">Deep Relationship Concatenation</a>

You can include recursive relationships into deep relationships by concatenating them with other relationships
using [staudenmeir/eloquent-has-many-deep](https://github.com/staudenmeir/eloquent-has-many-deep) (Laravel 9+).

Consider a `HasMany` relationship between `Node` and `Post` and building a deep relationship to get all posts of a
node's descendants:

`Node` → descendants → `Node` → has many → `Post`

[Install](https://github.com/staudenmeir/eloquent-has-many-deep/#installation) the additional package, add the
`HasRelationships` trait to the recursive model
and [define](https://github.com/staudenmeir/eloquent-has-many-deep/#concatenating-existing-relationships) a
deep relationship:

```php
class Node extends Model
{
    use \Staudenmeir\EloquentHasManyDeep\HasRelationships;
    use \Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

    public function descendantPosts()
    {
        return $this->hasManyDeepFromRelations(
            $this->descendants(),
            (new static)->posts()
        );
    }
    
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}

$descendantPosts = Node::find($id)->descendantPosts;
```

At the moment, recursive relationships can only be at the beginning of deep relationships:

- Supported: `Node` → descendants → `Node` → has many → `Post`
- Not supported: `Post` → belongs to → `Node` → descendants → `Node`

#### <a name="graphs-known-issues">Known Issues</a>

MariaDB [doesn't yet support](https://jira.mariadb.org/browse/MDEV-19077) correlated CTEs in subqueries. This affects
queries like `Node::whereHas('descendants')` or `Node::withCount('descendants')`.

### Package Conflicts

- `staudenmeir/eloquent-eager-limit`: Replace both packages
  with [staudenmeir/eloquent-eager-limit-x-laravel-adjacency-list](https://github.com/staudenmeir/eloquent-eager-limit-x-laravel-adjacency-list)
  to use them on the same model.
- `staudenmeir/eloquent-param-limit-fix`: Replace both packages
  with [staudenmeir/eloquent-param-limit-fix-x-laravel-adjacency-list](https://github.com/staudenmeir/eloquent-param-limit-fix-x-laravel-adjacency-list)
  to use them on the same model.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) and [CODE OF CONDUCT](.github/CODE_OF_CONDUCT.md) for details.

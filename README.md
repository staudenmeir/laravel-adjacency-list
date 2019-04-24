[![Build Status](https://travis-ci.org/staudenmeir/laravel-adjacency-list.svg?branch=master)](https://travis-ci.org/staudenmeir/laravel-adjacency-list)
[![Code Coverage](https://scrutinizer-ci.com/g/staudenmeir/laravel-adjacency-list/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/staudenmeir/laravel-adjacency-list/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/staudenmeir/laravel-adjacency-list/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/staudenmeir/laravel-adjacency-list/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/staudenmeir/laravel-adjacency-list/v/stable)](https://packagist.org/packages/staudenmeir/laravel-adjacency-list)
[![Total Downloads](https://poser.pugx.org/staudenmeir/laravel-adjacency-list/downloads)](https://packagist.org/packages/staudenmeir/laravel-adjacency-list)
[![License](https://poser.pugx.org/staudenmeir/laravel-adjacency-list/license)](https://packagist.org/packages/staudenmeir/laravel-adjacency-list)

## Introduction
This Laravel Eloquent extension provides recursive relationships using common table expressions (CTE).  
Supports Laravel 5.5.29+.

## Compatibility

- MySQL 8.0+
- MariaDB 10.2+
- PostgreSQL 9.4+
- SQLite 3.8.3+
- SQL Server 2008+
 
## Installation

    composer require staudenmeir/laravel-adjacency-list:"^1.0"

## Usage

- [Getting Started](#getting-started)
- [Relationships](#relationships)
- [Tree](#tree)
- [Filters](#filters)
- [Order](#order)
- [Depth](#depth)
- [Path](#path)

### Getting Started

Consider the following table schema for hierarchical data:

```php
Schema::create('users', function (Blueprint $table) {
    $table->increments('id');
    $table->unsignedInteger('parent_id')->nullable();
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

### Relationships

The trait provides various relationships:

- `ancestors()`: The model's recursive parents.
- `ancestorsAndSelf()`: The model's recursive parents and itself.
- `children()`: The model's direct children.
- `childrenAndSelf()`: The model's direct children and itself.
- `descendants()`: The model's recursive children.
- `descendantsAndSelf()`: The model's recursive children and itself.
- `parent()`: The model's direct parent.
- `parentAndSelf()`: The model's direct parent and itself.
- `siblings()`: The parent's other children.
- `siblingsAndSelf()`: All the parent's children.

```php
$ancestors = User::find($id)->ancestors;

$users = User::with('descendants')->get();

$users = User::whereHas('siblings', function ($query) {
    $query->where('name', '=', 'John');
})->get();
```

### Tree

The trait provides the `tree()` query scope to get all models, beginning at the root(s):

```php
$tree = User::tree()->get();
```

### Filters

The trait provides query scopes to filter models by their position in the tree:

- `hasChildren()`: Models with children.
- `hasParent()`: Models with a parent.
- `isLeaf()`: Models without children.
- `isRoot()`: Models without a parent.

```php
$noLeaves = User::hasChildren()->get();

$noRoots = User::hasParent()->get();

$leaves = User::isLeaf()->get();

$roots = User::isRoot()->get();
```

### Order

The trait provides query scopes to order models breadth-first or depth-first:

- `breadthFirst()`: Get siblings before children.
- `depthFirst()`: Get children before siblings.

```php
$tree = User::tree()->breadthFirst()->get();

$descendants = User::find($id)->descendants()->depthFirst()->get();
```

### Depth

The results of ancestor, descendant and tree queries include an additional `depth` column.

It contains the model's depth *relative* to the query's parent. The depth is positive for descendants and negative for ancestors:

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

You can use the `whereDepth()` query scope to filter models by their relative depth:

```php
$descendants = User::find($id)->descendants()->whereDepth(2)->get();

$descendants = User::find($id)->descendants()->whereDepth('<', 3)->get();
```

### Path

The results of ancestor, descendant and tree queries include an additional `path` column.

It contains the dot-separated path of primary keys from the query's parent to the model:

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
<?php

namespace Staudenmeir\LaravelAdjacencyList\IdeHelper;

use Barryvdh\LaravelIdeHelper\Console\ModelsCommand;
use Barryvdh\LaravelIdeHelper\Contracts\ModelHookInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Collection;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasGraphRelationships;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

/**
 * @template RT { name: string, manyRelation: boolean, comment: string }
 */
class RecursiveRelationsHook implements ModelHookInterface
{
    /**
     * @var array<array<RT>>
     */
    protected static array $treeRelationMap = [
        [
            'name' => 'ancestors',
            'manyRelation' => true,
            'comment' => "The model's recursive parents.",
        ],
        [
            'name' => 'ancestorsAndSelf',
            'manyRelation' => true,
            'comment' => "The model's recursive parents and itself.",
        ],
        [
            'name' => 'bloodline',
            'manyRelation' => true,
            'comment' => "The model's ancestors, descendants and itself.",
        ],
        [
            'name' => 'children',
            'manyRelation' => true,
            'comment' => "The model's direct children.",
        ],
        [
            'name' => 'childrenAndSelf',
            'manyRelation' => true,
            'comment' => "The model's direct children and itself.",
        ],
        [
            'name' => 'descendants',
            'manyRelation' => true,
            'comment' => "The model's recursive children.",
        ],
        [
            'name' => 'descendantsAndSelf',
            'manyRelation' => true,
            'comment' => "The model's recursive children and itself.",
        ],
        [
            'name' => 'parent',
            'manyRelation' => false,
            'comment' => "The model's direct parent.",
        ],
        [
            'name' => 'parentAndSelf',
            'manyRelation' => true,
            'comment' => "The model's direct parent and itself.",
        ],
        [
            'name' => 'rootAncestor',
            'manyRelation' => false,
            'comment' => "The model's topmost parent.",
        ],
        [
            'name' => 'siblings',
            'manyRelation' => true,
            'comment' => "The parent's other children.",
        ],
        [
            'name' => 'siblingsAndSelf',
            'manyRelation' => true,
            'comment' => "All the parent's children.",
        ]
    ];

    /**
     * @var array<array<RT>>
     */
    protected static array $graphRelationMap = [
        [
            'name' => 'ancestors',
            'manyRelation' => true,
            'comment' => "The node's recursive parents.",
        ],
        [
            'name' => 'ancestorsAndSelf',
            'manyRelation' => true,
            'comment' => "The node's recursive parents and itself.",
        ],
        [
            'name' => 'children',
            'manyRelation' => true,
            'comment' => "The node's direct children.",
        ],
        [
            'name' => 'childrenAndSelf',
            'manyRelation' => true,
            'comment' => "The node's direct children and itself.",
        ],
        [
            'name' => 'descendants',
            'manyRelation' => true,
            'comment' => "The node's recursive children.",
        ],
        [
            'name' => 'descendantsAndSelf',
            'manyRelation' => true,
            'comment' => "The node's recursive children and itself.",
        ],
        [
            'name' => 'parents',
            'manyRelation' => true,
            'comment' => "The node's direct parents.",
        ],
        [
            'name' => 'parentsAndSelf',
            'manyRelation' => true,
            'comment' => "The node's direct parents and itself.",
        ],
    ];

    public function run(ModelsCommand $command, Model $model): void
    {
        $traits = collect(
            class_uses_recursive($model)
        );

        if ($traits->contains(HasRecursiveRelationships::class)) {
            $this->setTreeRelationProperties($command, $model);
        }

        if ($traits->contains(HasGraphRelationships::class)) {
            $this->setGraphRelationProperties($command, $model);
        }
    }


    protected function setTreeRelationProperties(ModelsCommand $command, Model $model): void
    {
        foreach (static::$treeRelationMap as $relationDefinition) {
            $type = $relationDefinition['manyRelation']
                ? '\\' . Collection::class . '|' . class_basename($model) . '[]'
                : class_basename($model);

            $command->setProperty(
                $relationDefinition['name'],
                $type,
                true,
                false,
                $relationDefinition['comment'],
                !$relationDefinition['manyRelation']
            );

            if ($relationDefinition['manyRelation']) {
                $command->setProperty(
                    Str::snake($relationDefinition['name']) . '_count',
                    'int',
                    true,
                    false,
                    null,
                    true
                );
            }
        }
    }

    protected function setGraphRelationProperties(ModelsCommand $command, Model $model): void
    {
        foreach (static::$graphRelationMap as $relationDefinition) {
            $type = '\\' . EloquentCollection::class . '|' . class_basename($model) . '[]';

            $command->setProperty(
                $relationDefinition['name'],
                $type,
                true,
                false,
                $relationDefinition['comment'],
                !$relationDefinition['manyRelation']
            );

            if ($relationDefinition['manyRelation']) {
                $command->setProperty(
                    Str::snake($relationDefinition['name']) . '_count',
                    'int',
                    true,
                    false,
                    null,
                    true
                );
            }
        }
    }
}

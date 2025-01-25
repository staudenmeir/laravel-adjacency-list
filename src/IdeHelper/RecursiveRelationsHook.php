<?php

namespace Staudenmeir\LaravelAdjacencyList\IdeHelper;

use Barryvdh\LaravelIdeHelper\Console\ModelsCommand;
use Barryvdh\LaravelIdeHelper\Contracts\ModelHookInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Collection as TreeCollection;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Graph\Collection as GraphCollection;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasGraphRelationships;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

class RecursiveRelationsHook implements ModelHookInterface
{
    /**
     * @var list<array{name: string, manyRelation: boolean, comment: string}>
     */
    protected static array $treeRelationships = [
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
     * @var list<array{name: string, manyRelation: boolean, comment: string}>
     */
    protected static array $graphRelationships = [
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
        /** @var \Illuminate\Contracts\Config\Repository $config */
        $config = $command->getLaravel()->make('config');

        $traits = class_uses_recursive($model);

        $useGenericsSyntax = $config->get('ide-helper.use_generics_annotations', true);

        if (in_array(HasRecursiveRelationships::class, $traits)) {
            foreach (static::$treeRelationships as $relationship) {
                $type = $relationship['manyRelation']
                    ? ($useGenericsSyntax
                        ? '\\' . TreeCollection::class . '<int, \\' . $model::class . '>'
                        : '\\' . TreeCollection::class . '|\\' . $model::class . '[]')
                    : '\\' . $model::class;

                $this->addRelationship($command, $relationship, $type);
            }
        }

        if (in_array(HasGraphRelationships::class, $traits)) {
            foreach (static::$graphRelationships as $relationship) {
                $type = $useGenericsSyntax
                    ? '\\' . GraphCollection::class . '<int, \\' . $model::class . '>'
                    : '\\' . GraphCollection::class . '|\\' . $model::class . '[]';

                $this->addRelationship($command, $relationship, $type);
            }
        }
    }

    /**
     * @param array{name: string, manyRelation: boolean, comment: string} $relationship
     */
    protected function addRelationship(ModelsCommand $command, array $relationship, string $type): void
    {
        /** @var \Illuminate\Contracts\Config\Repository $config */
        $config = $command->getLaravel()->make('config');

        $command->setProperty(
            $relationship['name'],
            $type,
            true,
            false,
            $relationship['comment'],
            !$relationship['manyRelation']
        );

        $addCountProperties = $config->get('ide-helper.write_model_relation_count_properties', true);

        if ($relationship['manyRelation'] && $addCountProperties) {
            $command->setProperty(
                Str::snake($relationship['name']) . '_count',
                'int',
                true,
                false,
                null,
                true
            );
        }
    }
}

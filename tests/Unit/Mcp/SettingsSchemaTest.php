<?php

declare(strict_types=1);

use CraftCms\Cms\Mcp\Schema\SettingsSchema;
use CraftCms\Cms\Ui\Controls\Choice;
use CraftCms\Cms\Ui\Controls\Hidden;
use CraftCms\Cms\Ui\Controls\Lightswitch;
use CraftCms\Cms\Ui\Controls\Number;
use CraftCms\Cms\Ui\Controls\Table;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Nodes\Group;
use CraftCms\Cms\Ui\Nodes\Scope;
use CraftCms\Cms\Ui\Ui;

it('describes settings UIs as JSON Schema', function () {
    $ui = Ui::make([
        Field::make('Mode', Choice::make('mode')->options([
            ['label' => 'Fast', 'value' => 'fast'],
            ['label' => 'Careful', 'value' => 'careful'],
        ]))->required(),
        Group::make('limits', [
            Field::make('Maximum', Number::make('limit')->min(1)),
            Field::make('Strict', Lightswitch::make('strict')),
        ])->label('Limits')->instructions('How much is allowed.')->asField()->dependsOn('settings.enabled'),
        Scope::make('advanced', [
            Field::make('Label', Text::make('button.label')->maxLength(20)),
        ]),
        Field::make('Columns', Table::make('columns')),
        Field::make('Secret', Hidden::make('internal')),
        Field::make('Old', Text::make('old'))->hidden(),
    ]);

    expect(app(SettingsSchema::class)->fromUi($ui, ['mode' => 'fast', 'limit' => 10]))->toEqual([
        'type' => 'object',
        'properties' => [
            'mode' => [
                'title' => 'Mode',
                'default' => 'fast',
                'oneOf' => [['const' => 'fast', 'title' => 'Fast'], ['const' => 'careful', 'title' => 'Careful']],
            ],
            'limit' => [
                'title' => 'Maximum',
                'description' => 'Part of “Limits”: How much is allowed. Only applies when enabled is enabled.',
                'default' => 10,
                'type' => 'number',
                'minimum' => 1,
            ],
            'strict' => [
                'title' => 'Strict',
                'description' => 'Part of “Limits”: How much is allowed. Only applies when enabled is enabled.',
                'type' => 'boolean',
            ],
            'advanced' => [
                'type' => 'object',
                'properties' => [
                    'button' => [
                        'type' => 'object',
                        'properties' => ['label' => ['title' => 'Label', 'type' => 'string', 'maxLength' => 20]],
                        'additionalProperties' => true,
                    ],
                ],
                'additionalProperties' => true,
            ],
            'columns' => [
                'title' => 'Columns',
                'description' => 'Set in the control panel with a Table control; read an existing record’s settings for the value’s shape.',
            ],
        ],
        'additionalProperties' => true,
        'required' => ['mode'],
    ]);
});

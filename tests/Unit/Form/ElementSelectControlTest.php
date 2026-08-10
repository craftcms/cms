<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Form\Controls\ElementSelect;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\User\Elements\User;

it('uses an empty ordered list as its canonical default', function () {
    $payload = app(FormResolver::class)->resolve(
        Form::make([
            Field::make()->control(ElementSelect::make('related')->elementType(Entry::class)),
        ]),
        new FormContext(namespace: 'settings'),
    );

    expect($payload->values)->toBe(['settings' => ['related' => []]]);
});

it('uses one public Control for modern element relationship types', function (string $elementType, string $customElement) {
    $payload = app(FormResolver::class)->resolve(
        Form::make([
            Field::make()->control(
                ElementSelect::make('related')
                    ->elementType($elementType)
                    ->sources(['*'])
                    ->criteria(['siteId' => 1])
                    ->limit(3)
                    ->showSiteMenu(),
            ),
        ]),
        new FormContext(namespace: 'settings', values: ['settings' => ['related' => []]]),
    );

    expect($payload->nodes[0]->control->props)->toMatchArray([
        'elementType' => $elementType,
        'customElement' => $customElement,
        'elements' => [],
        'sources' => ['*'],
        'criteria' => ['siteId' => 1],
        'limit' => 3,
        'showSiteMenu' => true,
    ]);
})->with([
    'assets' => [Asset::class, 'craft-asset-select-input'],
    'entries' => [Entry::class, 'craft-entry-select-input'],
    'users' => [User::class, 'craft-element-select-input'],
]);

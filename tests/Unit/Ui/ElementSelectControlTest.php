<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Ui\Controls\ElementSelect;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiResolver;
use CraftCms\Cms\User\Elements\User;

it('uses an empty ordered list as its canonical default', function () {
    $payload = app(UiResolver::class)->resolve(
        Ui::make([
            Field::make()->control(ElementSelect::make('related')->elementType(Entry::class)),
        ]),
        new UiContext(namespace: 'settings'),
    );

    expect($payload->values)->toBe(['settings' => ['related' => []]]);
});

it('uses one public Control for modern element relationship types', function (string $elementType, string $customElement) {
    $payload = app(UiResolver::class)->resolve(
        Ui::make([
            Field::make()->control(
                ElementSelect::make('related')
                    ->elementType($elementType)
                    ->sources(['*'])
                    ->criteria(['siteId' => 1])
                    ->limit(3)
                    ->showSiteMenu(),
            ),
        ]),
        new UiContext(namespace: 'settings', values: ['settings' => ['related' => []]]),
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

it('resolves a chip’s status the way an element index chip does', function (string $status, array $expected) {
    $element = new class($status) extends Entry
    {
        public function __construct(private readonly string $stubStatus)
        {
            parent::__construct();
        }

        public function getStatus(): string
        {
            return $this->stubStatus;
        }

        public function showStatusIndicator(): bool
        {
            return true;
        }
    };

    $method = new ReflectionMethod(ElementSelect::class, 'statusPayload');

    // The same mapping `Cp\Html\StatusHtml` applies when it renders the
    // indicator server-side for an index chip.
    expect($method->invoke(null, $element))->toBe($expected);
})->with([
    'live' => ['live', ['fill' => 'teal', 'label' => 'Live', 'draft' => false]],
    'pending' => ['pending', ['fill' => 'orange', 'label' => 'Pending', 'draft' => false]],
    'expired' => ['expired', ['fill' => 'red', 'label' => 'Expired', 'draft' => false]],
]);

it('omits the status for an element type that doesn’t show one', function () {
    $element = new class extends Entry
    {
        public function showStatusIndicator(): bool
        {
            return false;
        }
    };

    $method = new ReflectionMethod(ElementSelect::class, 'statusPayload');

    expect($method->invoke(null, $element))->toBeNull();
});

it('defaults to the list view mode', function () {
    $control = ElementSelect::make('related')->elementType(Entry::class);

    expect($control->props()['viewMode'])->toBe(ElementSelect::VIEW_MODE_LIST);
});

it('carries the view mode through to its props', function (string $viewMode) {
    $control = ElementSelect::make('related')
        ->elementType(Entry::class)
        ->viewMode($viewMode);

    expect($control->props()['viewMode'])->toBe($viewMode);
})->with(ElementSelect::viewModes());

it('rejects a view mode the field can’t be set to', function () {
    ElementSelect::make('related')
        ->elementType(Entry::class)
        ->viewMode('carousel');
})->throws(InvalidArgumentException::class, 'Unknown element select view mode [carousel].');

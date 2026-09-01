<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Element\Contracts\ElementInterface;
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

/**
 * Card rendering needs a fully configured element; what's under test here is
 * which parts a mode asks for, so the renderer is stood in for.
 */
function fakeElementHtml(): void
{
    app()->instance(ElementHtml::class, new class extends ElementHtml
    {
        public function __construct() {}

        public function elementCardAttributes(ElementInterface $element, array $config = []): array
        {
            return [];
        }

        public function elementCardHeaderHtml(ElementInterface $element, array $config = []): string
        {
            return '';
        }

        public function elementCardContentHtml(ElementInterface $element, array $config = []): string
        {
            return '';
        }

        public function elementCardFooterHtml(ElementInterface $element, array $config = []): string
        {
            return '';
        }
    });
}

it('renders card parts only for the card view modes', function (string $viewMode, bool $expected) {
    fakeElementHtml();
    $method = new ReflectionMethod(ElementSelect::class, 'viewPayload');
    $keys = array_keys($method->invoke(null, new Entry, $viewMode));

    expect(in_array('cardHeaderHtml', $keys, true))->toBe($expected);
})->with([
    'list' => [ElementSelect::VIEW_MODE_LIST, false],
    'list-inline' => [ElementSelect::VIEW_MODE_LIST_INLINE, false],
    'thumbs' => [ElementSelect::VIEW_MODE_THUMBS, false],
    'cards' => [ElementSelect::VIEW_MODE_CARDS, true],
    'cards-grid' => [ElementSelect::VIEW_MODE_CARDS_GRID, true],
]);

it('renders thumbnails for list and thumbs view modes', function (string $viewMode, bool $expected) {
    fakeElementHtml();
    $method = new ReflectionMethod(ElementSelect::class, 'viewPayload');
    $keys = array_keys($method->invoke(null, new Entry, $viewMode));

    expect(in_array('thumbHtml', $keys, true))->toBe($expected);
})->with([
    'list' => [ElementSelect::VIEW_MODE_LIST, true],
    'list-inline' => [ElementSelect::VIEW_MODE_LIST_INLINE, true],
    'thumbs' => [ElementSelect::VIEW_MODE_THUMBS, true],
    'cards' => [ElementSelect::VIEW_MODE_CARDS, false],
]);

// The card component has its own thumbnail slot, so the thumb is provided apart
// from the content rather than baked into it and rendered twice.
it('provides a card thumbnail apart from the card content', function () {
    fakeElementHtml();
    $method = new ReflectionMethod(ElementSelect::class, 'cardPayload');
    $payload = $method->invoke(null, new Entry);

    expect($payload)->toHaveKeys(['cardThumbHtml', 'thumbAlignment']);
});

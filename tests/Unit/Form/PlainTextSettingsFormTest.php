<?php

declare(strict_types=1);

use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Ui\ControlPayload;
use CraftCms\Cms\Ui\Controls\Lightswitch;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\NodePayload;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Nodes\Group;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiPayload;
use CraftCms\Cms\Ui\UiResolver;
use Symfony\Component\DomCrawler\Crawler;

function plainTextPayload(): UiPayload
{
    $field = new PlainText([
        'uiMode' => 'normal',
        'placeholder' => 'Configured placeholder',
        'charLimit' => 50,
        'code' => false,
        'multiline' => true,
        'initialRows' => 4,
    ]);

    return app(UiResolver::class)->resolve($field->settingsUi(), new UiContext(
        namespace: 'settings',
        values: [
            'settings' => [
                'uiMode' => 'enlarged',
                'placeholder' => 'Submitted placeholder',
                'fieldLimit' => 120,
                'limitUnit' => 'chars',
                'code' => true,
                'multiline' => true,
                'initialRows' => 8,
            ],
        ],
        errors: [
            'placeholder' => ['Placeholder is invalid.'],
            'missing' => ['The settings could not be saved.'],
        ],
    ));
}

it('builds complete and incremental node lists with Conditionable authoring', function () {
    $form = Ui::make([
        Field::make('First', Text::make('first')),
    ])->add(
        Field::make('Second', Text::make(['second'])),
    )->when(true, fn (Ui $form) => $form->add(
        Field::make('Conditional', Text::make('conditional')),
    ))->when(false, fn (Ui $form) => $form->add(
        Field::make('Omitted', Text::make('omitted')),
    ))->unless(false, fn (Ui $form) => $form->add(
        Field::make('Unless', Text::make('unless')),
    ))->unless(true, fn (Ui $form) => $form->add(
        Field::make('Also omitted', Text::make('alsoOmitted')),
    ))->when(true, fn (Ui $form) => $form->add(
        Field::make('Third', Text::make('nested.third')),
    ));

    $payload = app(UiResolver::class)->resolve($form, new UiContext(namespace: 'settings'));

    expect($payload->nodes[0])->toBeInstanceOf(NodePayload::class)
        ->and($payload->nodes[0]->control)->toBeInstanceOf(ControlPayload::class)
        ->and(array_map(fn (NodePayload $node): array => $node->control?->path ?? [], $payload->nodes))
        ->toBe([
            ['settings', 'first'],
            ['settings', 'second'],
            ['settings', 'conditional'],
            ['settings', 'unless'],
            ['settings', 'nested', 'third'],
        ]);
});

it('rejects identities that cannot reconcile stably', function () {
    $duplicatePaths = Ui::make([
        Field::make('First', Text::make('same')),
        Field::make('Second', Text::make(['same'])),
    ]);
    $missingUid = Ui::make([
        Group::make('', [Field::make()->control(Text::make('child'))]),
    ]);
    $duplicateUids = Ui::make([
        Group::make('same'),
        Group::make('same'),
    ]);

    expect(fn () => app(UiResolver::class)->resolve($duplicatePaths, new UiContext))
        ->toThrow(InvalidArgumentException::class, 'Duplicate Control path')
        ->and(fn () => app(UiResolver::class)->resolve($missingUid, new UiContext))
        ->toThrow(InvalidArgumentException::class, 'stable UID')
        ->and(fn () => app(UiResolver::class)->resolve($duplicateUids, new UiContext))
        ->toThrow(InvalidArgumentException::class, 'Duplicate Node UID');
});

it('resolves empty Forms', function () {
    $payload = app(UiResolver::class)->resolve(Ui::make(), new UiContext(namespace: 'settings'));

    expect($payload->scope)->toBe(['settings'])
        ->and($payload->nodes)->toBe([])
        ->and($payload->values)->toBe([])
        ->and($payload->errors)->toBe([]);
});

it('keeps stable identities for pathless presentational leaves', function () {
    $payload = app(UiResolver::class)->resolve(
        Ui::make([Group::make('presentational')]),
        new UiContext,
    );

    expect($payload->nodes[0]->jsonSerialize())->toMatchArray([
        'uid' => 'presentational',
        'children' => [],
    ]);
});

it('only serializes reactive controls when enabled', function () {
    $payload = app(UiResolver::class)->resolve(Ui::make([
        Field::make('Static', Text::make('static')),
        Field::make('Reactive', Text::make('reactive')->reactive()),
        Group::make('dependent')->dependsOn('reactive'),
    ]), new UiContext);

    expect($payload->nodes[0]->control?->reactive)->toBeFalse()
        ->and($payload->nodes[1]->control?->reactive)->toBeTrue()
        ->and($payload->nodes[0]->control?->jsonSerialize())->not->toHaveKey('reactive')
        ->and($payload->nodes[1]->control?->jsonSerialize()['reactive'])->toBeTrue()
        ->and($payload->nodes[2]->props['dependsOn'])->toBe(['reactive']);
});

it('serializes switch configuration', function () {
    $control = Lightswitch::make('enabled')
        ->indeterminate()
        ->size('small')
        ->checkedValue('yes')
        ->indeterminateValue('maybe')
        ->onLabel('On')
        ->offLabel('Off');

    expect($control->props())->toBe([
        'indeterminate' => true,
        'size' => 'small',
        'checkedValue' => 'yes',
        'indeterminateValue' => 'maybe',
        'onLabel' => 'On',
        'offLabel' => 'Off',
    ]);
});

it('assigns descendant errors to the longest matching control path', function () {
    $form = Ui::make([
        Field::make()->control(Text::make('address')->value([])),
        Field::make()->control(Text::make('address.street')),
    ]);
    $payload = app(UiResolver::class)->resolve($form, new UiContext(
        namespace: 'settings',
        errors: ['address.street.line1' => ['Street is invalid.']],
    ));

    expect($payload->errors)->toBe([[
        'path' => ['settings', 'address', 'street'],
        'messages' => ['Street is invalid.'],
    ]]);
});

it('resolves Plain Text settings to the shared JSON-safe payload', function () {
    $expected = json_decode(
        file_get_contents(__DIR__.'/../../Fixtures/Form/plain-text-settings.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect(plainTextPayload()->jsonSerialize())->toBe($expected)
        ->and(json_encode(plainTextPayload(), JSON_THROW_ON_ERROR))->toBeString();
});

it('only includes Initial Rows for multiline fields', function (bool $multiline, bool $included) {
    $payload = app(UiResolver::class)->resolve(
        new PlainText(['multiline' => $multiline])->settingsUi(),
        new UiContext(namespace: 'settings'),
    );

    expect(array_key_exists('initialRows', $payload->values['settings']))->toBe($included);
})->with([
    'single line' => [false, false],
    'multiline' => [true, true],
]);

it('renders an accessible editable PHP form with ordinary nested names', function () {
    $crawler = new Crawler(app(UiHtmlRenderer::class)->render(plainTextPayload()));

    expect($crawler->filter('input[name="settings[placeholder]"][value="Submitted placeholder"]'))->toHaveCount(1)
        ->and($crawler->filter('select[name="settings[uiMode]"] option[value="enlarged"][selected]'))->toHaveCount(1)
        ->and($crawler->filter('input[name="settings[code]"][value="1"]'))->toHaveCount(1)
        ->and($crawler->filter('input[name="settings[placeholder]"][aria-invalid="true"]'))->toHaveCount(1)
        ->and($crawler->filter('craft-field[label="Placeholder Text"]'))->toHaveCount(1)
        ->and($crawler->filter('[role="alert"]')->text())->toContain('The settings could not be saved.');
});

it('uses the payload renderer for the production Plain Text PHP settings form', function () {
    $field = new PlainText(['placeholder' => 'Production value', 'multiline' => false]);
    $field->errors()->add('fieldLimit', 'The field limit is invalid.');
    $editableContext = new UiContext(errors: $field->errors()->getMessages());
    $editable = new Crawler(app(UiHtmlRenderer::class)->render(
        app(UiResolver::class)->resolve($field->settingsUi($editableContext), $editableContext),
    ));
    $readOnlyContext = new UiContext(mode: ControlMode::ReadOnly);
    $readOnly = new Crawler(app(UiHtmlRenderer::class)->render(
        app(UiResolver::class)->resolve($field->settingsUi($readOnlyContext), $readOnlyContext),
    ));

    expect($editable->filter('[data-ui-node="plain-text-field-limit"]'))->toHaveCount(1)
        ->and($editable->filter('input[name="placeholder"][value="Production value"]'))->toHaveCount(1)
        ->and($editable->filter('input[name="fieldLimit"][aria-invalid="true"]'))->toHaveCount(1)
        ->and($editable->filter('input[name="initialRows"]'))->toHaveCount(0)
        ->and($editable->filter('craft-field[has-errors] [slot="feedback"]')->text())->toContain('The field limit is invalid.')
        ->and($readOnly->filter('input[value="Production value"]'))->toHaveCount(1)
        ->and($readOnly->filter('[name]'))->toHaveCount(0);
});

it('displays values without names in non-editable PHP modes', function (string $mode) {
    $payload = app(UiResolver::class)->resolve(
        new PlainText(['placeholder' => 'Visible'])->settingsUi(),
        new UiContext(namespace: 'settings', mode: $mode),
    );
    $crawler = new Crawler(app(UiHtmlRenderer::class)->render($payload));

    expect($crawler->filter('input[value="Visible"]'))->toHaveCount(1)
        ->and($crawler->filter('[name]'))->toHaveCount(0)
        ->and($crawler->filter('select[disabled]'))->not->toHaveCount(0)
        ->and($crawler->filter('craft-switch[disabled]'))->not->toHaveCount(0);
})->with(['readOnly', 'disabled']);

it('resolves translated copy before either renderer receives the payload', function () {
    I18N::withLocale('de', null, function () {
        $payload = app(UiResolver::class)->resolve(new PlainText()->settingsUi(), new UiContext);

        expect($payload->nodes[0]->props['label'])->toBe('UI-Modus')
            ->and(app(UiHtmlRenderer::class)->render($payload))->toContain('UI-Modus');
    });
});

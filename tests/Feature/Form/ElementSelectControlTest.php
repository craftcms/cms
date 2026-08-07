<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Form\Controls\ElementSelect;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\User\Models\User as UserModel;
use Symfony\Component\DomCrawler\Crawler;

it('resolves and renders ordered element relationships', function () {
    $first = Entry::factory()->title('First entry')->create();
    $second = Entry::factory()->title('Second entry')->create();
    $form = Form::make([
        Field::make('Related entries',
            ElementSelect::make('related')
                ->elementType(EntryElement::class)
                ->selectionLabel('Add an entry'),
        ),
    ]);
    $payload = app(FormResolver::class)->resolve($form, new FormContext(
        namespace: 'settings',
        values: ['settings' => ['related' => [$second->id, $first->id]]],
        errors: ['related' => ['Choose valid entries.']],
    ));
    $control = $payload->nodes[0]->control;
    $crawler = new Crawler(app(FormHtmlRenderer::class)->render($payload));

    expect($control->component)->toBe('craft:element-select')
        ->and($control->props['elements'])->toMatchArray([
            ['id' => $second->id, 'label' => 'Second entry', 'siteId' => 1],
            ['id' => $first->id, 'label' => 'First entry', 'siteId' => 1],
        ])
        ->and($crawler->filter('craft-chip')->each(
            fn (Crawler $chip) => $chip->filter('input')->attr('value'),
        ))->toBe([(string) $second->id, (string) $first->id])
        ->and($crawler->filter('input[name="settings[related][]"]'))->toHaveCount(2)
        ->and($crawler->filter('craft-entry-select-input'))->toHaveCount(1)
        ->and($crawler->text())->toContain('Choose valid entries.');
});

it('displays element relationships without submitting them in non-editable modes', function (ControlMode $mode) {
    $entry = Entry::factory()->title('Related entry')->create();
    $form = Form::make([
        Field::make()->control(ElementSelect::make('related')->elementType(EntryElement::class)),
    ]);
    $payload = app(FormResolver::class)->resolve($form, new FormContext(
        namespace: 'settings',
        values: ['settings' => ['related' => [$entry->id]]],
        mode: $mode,
    ));
    $crawler = new Crawler(app(FormHtmlRenderer::class)->render($payload));

    expect($crawler->filter('craft-chip'))->toHaveCount(1)
        ->and($crawler->filter('input[name], select[name], textarea[name]'))->toHaveCount(0)
        ->and($crawler->filter('.add'))->toHaveCount(0);
})->with([
    'read-only' => ControlMode::ReadOnly,
    'disabled' => ControlMode::Disabled,
]);

it('keeps current values when picker criteria change', function () {
    $entry = Entry::factory()->title('Existing entry')->create();
    $form = Form::make([
        Field::make()->control(
            ElementSelect::make('related')
                ->elementType(EntryElement::class)
                ->criteria(['id' => 999999]),
        ),
    ]);
    $payload = app(FormResolver::class)->resolve($form, new FormContext(
        namespace: 'settings',
        values: ['settings' => ['related' => [$entry->id]]],
    ));
    $crawler = new Crawler(app(FormHtmlRenderer::class)->render($payload));

    expect($payload->nodes[0]->control->props['elements'][0]['id'])->toBe($entry->id)
        ->and($crawler->filter("input[value=\"{$entry->id}\"]"))->toHaveCount(1);
});

it('resolves non-empty modern relationship values', function (Closure $createElement) {
    $element = $createElement();
    $form = Form::make([
        Field::make()->control(
            ElementSelect::make('related')->elementType($element::class),
        ),
    ]);
    $payload = app(FormResolver::class)->resolve($form, new FormContext(
        namespace: 'settings',
        values: ['settings' => ['related' => [$element->getId()]]],
    ));

    expect($payload->nodes[0]->control->props['elements'][0]['id'])->toBe($element->getId());
})->with([
    'assets' => fn () => AssetModel::factory()->createElement(),
    'entries' => fn () => EntryElement::find()->id(Entry::factory()->create()->id)->one(),
    'users' => fn () => UserModel::factory()->createElement(),
]);

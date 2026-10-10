<?php

declare(strict_types=1);

use CraftCms\Cms\Ui\Controls\Choice;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Enums\FieldWidth;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Nodes\Group;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiResolver;
use Symfony\Component\DomCrawler\Crawler;

function locationGroup(): Group
{
    return Group::make('asset-location', [
        Field::make()
            ->control(Choice::make('restrictedLocationSource')->options([
                ['label' => 'Uploads', 'value' => 'volume:uploads'],
            ]))
            ->width(FieldWidth::Third),
        Field::make()
            ->control(Text::make('restrictedLocationSubpath'))
            ->width(FieldWidth::TwoThirds),
    ])->label('Asset Location');
}

function renderGroup(Group $group): Crawler
{
    $context = new UiContext;
    $payload = app(UiResolver::class)->resolve(Ui::make([$group]), $context);

    return new Crawler(app(UiHtmlRenderer::class)->render($payload));
}

it('renders a section as a fieldset with a legend by default', function () {
    $crawler = renderGroup(locationGroup()->width(FieldWidth::Quarter));

    expect($crawler->filter('fieldset > legend')->text())->toBe('Asset Location')
        ->and($crawler->filter('fieldset')->attr('class'))->toContain('width-25')
        ->and($crawler->filter('craft-field[fieldset]'))->toHaveCount(0);
});

it('renders a craft-field in fieldset mode with asField()', function () {
    $crawler = renderGroup(
        locationGroup()
            ->asField()
            ->instructions('The location where assets can be selected from.')
            ->instructionsPosition('after')
            ->layoutUid('layout-element-uid')
            ->width(FieldWidth::Half),
    );
    $field = $crawler->filter('craft-field[fieldset]');

    expect($field)->toHaveCount(1)
        ->and($field->attr('label'))->toBe('Asset Location')
        ->and($field->attr('class'))->toContain('width-50')
        ->and($field->attr('instructions-position'))->toBe('after')
        ->and($field->attr('data-layout-element'))->toBe('layout-element-uid')
        ->and($crawler->filter('legend'))->toHaveCount(0)
        ->and($field->filter('craft-field.width-33'))->toHaveCount(1)
        ->and($field->filter('craft-field.width-66'))->toHaveCount(1);
});

it('keeps child control paths at the surrounding namespace in either appearance', function () {
    $context = new UiContext(namespace: 'settings');
    $paths = fn (Group $group): array => array_map(
        fn ($node): array => $node->control->path,
        app(UiResolver::class)->resolve(Ui::make([$group]), $context)->nodes[0]->children,
    );
    $expected = [
        ['settings', 'restrictedLocationSource'],
        ['settings', 'restrictedLocationSubpath'],
    ];

    expect($paths(locationGroup()))->toBe($expected)
        ->and($paths(locationGroup()->asField()))->toBe($expected);
});

it('drops collapsible in field appearance', function () {
    $context = new UiContext;
    $payload = app(UiResolver::class)->resolve(
        Ui::make([locationGroup()->collapsible()->asField()]),
        $context,
    );

    expect($payload->nodes[0]->props)->not->toHaveKey('collapsible')
        ->and($payload->nodes[0]->props['asField'])->toBeTrue();
});

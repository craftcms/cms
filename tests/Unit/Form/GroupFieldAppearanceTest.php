<?php

declare(strict_types=1);

use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Enums\FieldWidth;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Form\Nodes\Group;
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
    $context = new FormContext;
    $payload = app(FormResolver::class)->resolve(Form::make([$group]), $context);

    return new Crawler(app(FormHtmlRenderer::class)->render($payload));
}

it('renders a section as a fieldset with a legend by default', function () {
    $crawler = renderGroup(locationGroup());

    expect($crawler->filter('fieldset > legend')->text())->toBe('Asset Location')
        ->and($crawler->filter('craft-field[fieldset]'))->toHaveCount(0);
});

it('renders a craft-field in fieldset mode with asField()', function () {
    $crawler = renderGroup(
        locationGroup()
            ->asField()
            ->instructions('The location where assets can be selected from.')
            ->width(FieldWidth::Half),
    );
    $field = $crawler->filter('craft-field[fieldset]');

    expect($field)->toHaveCount(1)
        ->and($field->attr('label'))->toBe('Asset Location')
        ->and($field->attr('class'))->toContain('width-50')
        ->and($crawler->filter('legend'))->toHaveCount(0)
        ->and($field->filter('craft-field.width-33'))->toHaveCount(1)
        ->and($field->filter('craft-field.width-66'))->toHaveCount(1);
});

it('keeps child control paths at the surrounding namespace in either appearance', function () {
    $context = new FormContext(namespace: 'settings');
    $paths = fn (Group $group): array => array_map(
        fn ($node): array => $node->control->path,
        app(FormResolver::class)->resolve(Form::make([$group]), $context)->nodes[0]->children,
    );
    $expected = [
        ['settings', 'restrictedLocationSource'],
        ['settings', 'restrictedLocationSubpath'],
    ];

    expect($paths(locationGroup()))->toBe($expected)
        ->and($paths(locationGroup()->asField()))->toBe($expected);
});

it('drops collapsible in field appearance', function () {
    $context = new FormContext;
    $payload = app(FormResolver::class)->resolve(
        Form::make([locationGroup()->collapsible()->asField()]),
        $context,
    );

    expect($payload->nodes[0]->props)->not->toHaveKey('collapsible')
        ->and($payload->nodes[0]->props['asField'])->toBeTrue();
});

<?php

declare(strict_types=1);

use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Nodes\Group;
use CraftCms\Cms\Ui\Nodes\Slot;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiResolver;
use Symfony\Component\DomCrawler\Crawler;

function renderUi(Ui $ui): array
{
    $payload = app(UiResolver::class)->resolve($ui, new UiContext(values: ['name' => 'Ada']));

    return [$payload, new Crawler(app(UiHtmlRenderer::class)->render($payload))];
}

it('hides a section group’s legend visually when its label is for screen readers only', function () {
    [, $crawler] = renderUi(Ui::make([
        Group::make('hidden-label', [])->label('Hidden')->labelSrOnly(),
        Group::make('shown-label', [])->label('Shown'),
    ]));

    expect($crawler->filter('[data-ui-node="hidden-label"] > legend.sr-only')->text())->toBe('Hidden')
        ->and($crawler->filter('[data-ui-node="shown-label"] > legend')->attr('class'))->toBeNull();
});

it('renders a flat group’s children without a fieldset or grid of its own', function () {
    [$payload, $crawler] = renderUi(Ui::make([
        Group::make('flat', [Field::make('Name', Text::make('name'))])->flat(),
    ]));

    expect($payload->nodes[0]->props['flat'])->toBeTrue()
        ->and($crawler->filter('div.group-flat[data-ui-node="flat"] > craft-field'))->toHaveCount(1)
        ->and($crawler->filter('[data-ui-node="flat"] craft-field-group'))->toHaveCount(0)
        ->and($crawler->filter('fieldset'))->toHaveCount(0);
});

it('leaves an empty placeholder for a slot the page fills', function () {
    [$payload, $crawler] = renderUi(Ui::make([Slot::make('mapping-actions')]));

    expect($payload->nodes[0]->component)->toBe('craft:slot')
        ->and($payload->nodes[0]->props)->toBe(['name' => 'mapping-actions'])
        ->and($crawler->filter('[data-ui-slot="mapping-actions"]')->html())->toBe('');
});

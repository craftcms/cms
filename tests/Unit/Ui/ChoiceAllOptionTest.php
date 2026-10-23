<?php

declare(strict_types=1);

use CraftCms\Cms\Ui\Controls\Choice;
use CraftCms\Cms\Ui\Enums\AllOptionMode;
use CraftCms\Cms\Ui\Enums\ChoicePresentation;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiResolver;
use Symfony\Component\DomCrawler\Crawler;

function sourcesChoice(?callable $configure = null, ?array $value = null): Crawler
{
    $control = Choice::make('sources')
        ->multiple()
        ->presentation(ChoicePresentation::Checkboxes)
        ->options([
            ['label' => 'Uploads', 'value' => 'volume:uploads'],
            ['label' => 'Images', 'value' => 'volume:images'],
        ])
        ->value($value ?? ['volume:uploads']);

    if ($configure !== null) {
        $configure($control);
    }

    $context = new UiContext;
    $payload = app(UiResolver::class)->resolve(
        Ui::make([Field::make('Sources', $control)]),
        $context,
    );

    return new Crawler(app(UiHtmlRenderer::class)->render($payload));
}

it('renders no select-all by default', function () {
    expect(sourcesChoice()->filter('.all'))->toHaveCount(0);
});

it('renders the options in a checkbox select, after a select-all checkbox', function () {
    $crawler = sourcesChoice(fn (Choice $choice) => $choice->allOption());
    $all = $crawler->filter('craft-checkbox-select > .cp-checkbox-select__item.all');

    expect($all)->toHaveCount(1)
        ->and($all->filter('label[slot="label"]')->text())->toBe('All')
        ->and($crawler->filter('craft-checkbox-select')->attr('all-label'))->toBe('All')
        // All posts under the same name as the options.
        ->and($crawler->filter('input[name="sources[]"]')->extract(['value']))
        ->toBe(['*', 'volume:uploads', 'volume:images']);
});

it('takes a custom label', function () {
    expect(sourcesChoice(fn (Choice $choice) => $choice->allOption('All sources'))
        ->filter('.all label[slot="label"]')->text())->toBe('All sources');
});

it('posts a single token by default, and only that token when checked', function () {
    $all = sourcesChoice(fn (Choice $choice) => $choice->allOption())
        ->filter('.all input[type="checkbox"]');

    expect($all->attr('value'))->toBe('*')
        ->and($all->attr('name'))->toBe('sources[]')
        ->and($all->attr('checked'))->toBeNull();

    // With the token selected the options render checked — so the list still
    // reads as fully selected — but nameless, so only the token posts. They
    // stay enabled, so “All” can clear them again, and remember their name.
    $crawler = sourcesChoice(fn (Choice $choice) => $choice->allOption(), ['*']);
    $options = $crawler->filter('.cp-checkbox-select__item:not(.all) input[type="checkbox"]');

    expect($crawler->filter('.all input[type="checkbox"]')->attr('checked'))->not->toBeNull()
        ->and($options)->toHaveCount(2)
        ->and($options->each(fn ($i) => $i->attr('checked')))->each->not->toBeNull()
        ->and($options->each(fn ($i) => $i->attr('name')))->each->toBeNull()
        ->and($options->each(fn ($i) => $i->attr('data-governed-name')))->each->toBe('sources[]')
        ->and($options->each(fn ($i) => $i->attr('disabled')))->each->toBeNull()
        // Only the token posts.
        ->and($crawler->filter('input[name="sources[]"]')->extract(['value']))->toBe(['*']);
});

it('carries no value of its own in each-value mode', function () {
    $crawler = sourcesChoice(fn (Choice $choice) => $choice->allOption(mode: AllOptionMode::EachValue));

    expect($crawler->filter('.all input[type="checkbox"]')->attr('name'))->toBeNull()
        ->and($crawler->filter('craft-checkbox-select')->attr('all-mode'))->toBe('each-value')
        ->and($crawler->filter('input[name="sources[]"]')->extract(['value']))
        ->toBe(['volume:uploads', 'volume:images']);
});

it('takes a custom token', function () {
    expect(sourcesChoice(fn (Choice $choice) => $choice->allOption(value: 'everything'), ['everything'])
        ->filter('.all input[type="checkbox"]')->attr('value'))->toBe('everything');
});

it('only advertises the label when the option is enabled', function () {
    $props = fn (?callable $configure): array => app(UiResolver::class)->resolve(
        Ui::make([Field::make('Sources', (function () use ($configure) {
            $c = Choice::make('sources')->multiple()->presentation(ChoicePresentation::Checkboxes)
                ->options([['label' => 'Uploads', 'value' => 'volume:uploads']]);
            if ($configure) {
                $configure($c);
            }

            return $c;
        })())]),
        new UiContext,
    )->nodes[0]->control->props;

    expect($props(null))->not->toHaveKey('allLabel')
        ->and($props(fn (Choice $c) => $c->allOption())['allLabel'])->toBe('All');
});

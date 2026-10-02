<?php

declare(strict_types=1);

use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Enums\AllOptionMode;
use CraftCms\Cms\Form\Enums\ChoicePresentation;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Field;
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

    $context = new FormContext;
    $payload = app(FormResolver::class)->resolve(
        Form::make([Field::make('Sources', $control)]),
        $context,
    );

    return new Crawler(app(FormHtmlRenderer::class)->render($payload));
}

it('renders no select-all by default', function () {
    expect(sourcesChoice()->filter('craft-checkbox-indeterminate'))->toHaveCount(0);
});

it('nests the options inside a select-all checkbox', function () {
    $crawler = sourcesChoice(fn (Choice $choice) => $choice->allOption());
    $all = $crawler->filter('craft-checkbox-group craft-checkbox-indeterminate');

    // The label is slotted, matching how Checkbox and Radio render theirs.
    expect($all)->toHaveCount(1)
        ->and($all->filter('label[slot="label"]')->text())->toBe('All')
        // Nesting is how the component finds the boxes it governs.
        ->and($all->filter('craft-checkbox'))->toHaveCount(2)
        // All posts under the same name as the options.
        ->and($crawler->filter('input[name="sources[]"]')->extract(['value']))
        ->toBe(['*', 'volume:uploads', 'volume:images']);
});

it('takes a custom label', function () {
    expect(sourcesChoice(fn (Choice $choice) => $choice->allOption('All sources'))
        ->filter('craft-checkbox-indeterminate > label[slot="label"]')->text())->toBe('All sources');
});

it('posts a single token by default, and only that token when checked', function () {
    $all = sourcesChoice(fn (Choice $choice) => $choice->allOption())
        ->filter('craft-checkbox-indeterminate > input[type="checkbox"]');

    expect($all->attr('value'))->toBe('*')
        ->and($all->attr('name'))->toBe('sources[]')
        ->and($all->attr('checked'))->toBeNull();

    // With the token selected the options render checked — so the group still
    // reads as fully selected — but nameless, so only the token posts. They
    // stay enabled: Lion skips disabled children when “All” propagates, which
    // would leave it unable to clear them again.
    $crawler = sourcesChoice(fn (Choice $choice) => $choice->allOption(), ['*']);
    $options = $crawler->filter('craft-checkbox input[type="checkbox"]');

    expect($crawler->filter('craft-checkbox-indeterminate > input')->attr('checked'))->not->toBeNull()
        ->and($options)->toHaveCount(2)
        ->and($options->each(fn ($i) => $i->attr('checked')))->each->not->toBeNull()
        ->and($options->each(fn ($i) => $i->attr('name')))->each->toBeNull()
        ->and($options->each(fn ($i) => $i->attr('disabled')))->each->toBeNull()
        // Only the token posts.
        ->and($crawler->filter('input[name="sources[]"]')->extract(['value']))->toBe(['*']);
});

it('carries no value of its own in each-value mode', function () {
    $crawler = sourcesChoice(fn (Choice $choice) => $choice->allOption(mode: AllOptionMode::EachValue));

    expect($crawler->filter('craft-checkbox-indeterminate')->attr('name'))->toBeNull()
        ->and($crawler->filter('craft-checkbox input[type="checkbox"]')->extract(['value']))
        ->toBe(['volume:uploads', 'volume:images']);
});

it('takes a custom token', function () {
    expect(sourcesChoice(fn (Choice $choice) => $choice->allOption(value: 'everything'), ['everything'])
        ->filter('craft-checkbox-indeterminate > input')->attr('value'))->toBe('everything');
});

it('only advertises the label when the option is enabled', function () {
    $props = fn (?callable $configure): array => app(FormResolver::class)->resolve(
        Form::make([Field::make('Sources', (function () use ($configure) {
            $c = Choice::make('sources')->multiple()->presentation(ChoicePresentation::Checkboxes)
                ->options([['label' => 'Uploads', 'value' => 'volume:uploads']]);
            if ($configure) {
                $configure($c);
            }

            return $c;
        })())]),
        new FormContext,
    )->nodes[0]->control->props;

    expect($props(null))->not->toHaveKey('allLabel')
        ->and($props(fn (Choice $c) => $c->allOption())['allLabel'])->toBe('All');
});

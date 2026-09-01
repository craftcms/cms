<?php

declare(strict_types=1);

use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Enums\ChoicePresentation;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Field;
use Symfony\Component\DomCrawler\Crawler;

/**
 * A `<select>` with no selected option shows its first one, so an optional
 * setting that was never set looks set — and saving posts nothing, because
 * nothing changed. These cover the blank option that makes the empty state
 * visible, and the cases that don't need one.
 *
 * @param  array<string, mixed>  $values
 */
function choiceCrawler(Choice $control, bool $required = false, array $values = []): Crawler
{
    $field = Field::make('Volume', $control);

    if ($required) {
        $field->required();
    }

    $payload = app(FormResolver::class)->resolve(
        Form::make([$field]),
        new FormContext(namespace: 'settings', values: ['settings' => $values]),
    );

    return new Crawler(app(FormHtmlRenderer::class)->render($payload));
}

/** @return list<string> */
function choiceOptionValues(Crawler $crawler): array
{
    return $crawler->filter('select[name="settings[volume]"] option')->each(
        fn (Crawler $option) => (string) $option->attr('value'),
    );
}

function volumeChoice(): Choice
{
    return Choice::make('volume')->options([
        ['label' => 'Images', 'value' => 'volume:images'],
        ['label' => 'Documents', 'value' => 'volume:docs'],
    ]);
}

it('offers a blank option so an unset value is visible as unset', function () {
    $crawler = choiceCrawler(volumeChoice());

    expect(choiceOptionValues($crawler))->toBe(['', 'volume:images', 'volume:docs'])
        // The blank is the one selected — without it nothing would be, and the
        // browser would show "Images" for a setting that was never set.
        ->and($crawler->filter('select[name="settings[volume]"] option[selected]')->attr('value'))->toBe('');
});

it('keeps the blank once a value is chosen, so it can be unset again', function () {
    $crawler = choiceCrawler(volumeChoice(), values: ['volume' => 'volume:docs']);

    expect(choiceOptionValues($crawler))->toBe(['', 'volume:images', 'volume:docs'])
        ->and($crawler->filter('select option[value="volume:docs"][selected]'))->toHaveCount(1);
});

it('omits the blank when the field is required', function () {
    $crawler = choiceCrawler(volumeChoice(), required: true);

    expect(choiceOptionValues($crawler))->toBe(['volume:images', 'volume:docs']);
});

it('leaves options that already carry an empty value alone', function () {
    $control = Choice::make('volume')->options([
        ['label' => 'None', 'value' => ''],
        ['label' => 'Images', 'value' => 'volume:images'],
    ]);

    expect(choiceOptionValues(choiceCrawler($control)))
        ->toBe(['', 'volume:images']);
});

it('leaves a multi-select alone, which shows its empty state already', function () {
    // `multiple()` alone presents as checkboxes; the `<select multiple>` is
    // what this rule has to leave untouched.
    $crawler = choiceCrawler(
        volumeChoice()->multiple()->presentation(ChoicePresentation::Select),
    );

    expect($crawler->filter('select[multiple] option')->each(
        fn (Crawler $option) => (string) $option->attr('value'),
    ))->toBe(['volume:images', 'volume:docs']);
});

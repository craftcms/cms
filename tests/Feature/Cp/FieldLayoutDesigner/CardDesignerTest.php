<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Cp\Enums\Theme;
use CraftCms\Cms\Cp\FieldLayoutDesigner\CardDesigner;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Assets;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\User\Elements\User;
use Symfony\Component\DomCrawler\Crawler;

function cardPreview(string $elementType): Crawler
{
    $html = app(CardDesigner::class)->previewHtml(new FieldLayout(['type' => $elementType]));

    return new Crawler("<div>$html</div>");
}

it('shows a sample chip above the card', function () {
    $preview = cardPreview(Entry::class);
    $chip = $preview->filter('craft-chip.cvd-chip');

    expect($chip->count())->toBe(1)
        ->and($chip->text())->toContain('The Quick Brown Fox')
        ->and($preview->filter('craft-card .label')->text())->toContain('The Quick Brown Fox')
        ->and($preview->filter('div > *')->first()->nodeName())->toBe('craft-chip');
});

it('shows the status in the chip and card for element types with statuses', function (string $elementType, bool $hasStatuses) {
    $preview = cardPreview($elementType);
    $chip = $preview->filter('craft-chip.cvd-chip');

    expect($chip->filter('[slot="status"]')->count())->toBe((int) $hasStatuses)
        ->and($chip->attr('show-status'))->toBe($hasStatuses ? '' : null)
        ->and($preview->filter('craft-card ul')->count())->toBe((int) $hasStatuses);
})->with([
    'entries' => [Entry::class, true],
    'addresses' => [Address::class, false],
]);

it('shows the sample entry as live', function () {
    $preview = cardPreview(Entry::class);

    expect($preview->filter('craft-card ul')->text())->toContain('Live')
        ->and($preview->filter('craft-card ul')->text())->not->toContain('Pending');
});

it('previews users as an active sample user with a default avatar and no status badge', function () {
    $preview = cardPreview(User::class);
    $chip = $preview->filter('craft-chip.cvd-chip');
    $card = $preview->filter('craft-card');

    expect($chip->text())->toContain('Olive Craften')
        ->and($card->filter('.label')->text())->toContain('Olive Craften')
        ->and($chip->filter('[slot="thumbnail"] svg title')->text())->toBe('Olive Craften')
        ->and($chip->filter('[slot="thumbnail"] svg text')->text())->toBe('OC')
        ->and($card->filter('[slot="thumbnail"] svg text')->text())->toBe('OC')
        ->and($chip->filter('[slot="status"] craft-indicator')->attr('label'))->toContain('Active')
        ->and($card->filter('ul')->count())->toBe(0);
});

it('shows a thumbnail in the chip when the cards have one', function (string $elementType, bool $hasThumb) {
    $chip = cardPreview($elementType)->filter('craft-chip.cvd-chip');

    expect($chip->filter('[slot="thumbnail"]')->count())->toBe((int) $hasThumb)
        ->and($chip->attr('show-thumb'))->toBe($hasThumb ? '' : null);
})->with([
    'entries without a thumbnail field' => [Entry::class, false],
    'assets' => [Asset::class, true],
]);

it('renders the thumbnail alignment setting as radios shown as buttons', function () {
    $layout = new FieldLayout(['type' => Asset::class]);
    $layout->setCardThumbAlignment('start');
    $designer = new Crawler('<div>'.app(CardDesigner::class)->html($layout).'</div>');

    // Radios, rather than toggle buttons, so the whole setting is one tab stop
    // whose selection the arrow keys move.
    $group = $designer->filter('craft-radio-group#thumb-alignment[buttons]');
    $radios = $group->filter('input[type="radio"][name="thumb-alignment"]');

    expect($group->count())->toBe(1)
        ->and($radios->each(fn (Crawler $radio) => $radio->attr('value')))->toBe(['start', 'end'])
        ->and($group->filter('input[value="start"]')->attr('checked'))->not->toBeNull()
        ->and($group->filter('input[value="end"]')->attr('checked'))->toBeNull()
        ->and($designer->filter('craft-tooltip[for="thumb-alignment-start"]')->text())->toBe('Left')
        ->and($designer->filter('craft-tooltip[for="thumb-alignment-end"]')->text())->toBe('Right')
        ->and($designer->filter('craft-button-group#thumb-alignment')->count())->toBe(0);
});

it('previews cards in the thumb style, once it applies', function (?string $style, bool $thumbField, bool $cover) {
    $layout = $thumbField
        ? EntryModel::factory()->withField('images', Assets::class)->createElementWithFields()->element->getFieldLayout()
        : new FieldLayout(['type' => Asset::class]);

    if ($thumbField) {
        $layout->thumbFieldKey = 'layoutElement:'.$layout->getCustomFieldElements()[0]->uid;
    }

    $layout->setCardThumbStyle($style);
    $card = new Crawler('<div>'.app(CardDesigner::class)->previewHtml($layout).'</div>')->filter('craft-card');

    expect($card->attr('cover-src') !== null)->toBe($cover)
        ->and($card->filter('[slot="thumbnail"]')->count())->toBe($cover ? 0 : 1);
})->with([
    'asset volume by default' => [null, false, true],
    'asset volume set to normal' => ['normal', false, false],
    'entry type by default' => [null, true, false],
    'entry type set to cover' => ['cover', true, true],
]);

it('previews cards with the sample image for the theme being shown', function (?string $cookie, string $theme, string $luminance) {
    if ($cookie !== null) {
        request()->cookies->set(Theme::COOKIE, $cookie);
    }

    $preview = cardPreview(Asset::class);
    $card = $preview->filter('craft-card');

    expect($card->attr('cover-src'))->toEndWith("bachelor-$theme.jpg")
        ->and($card->attr('luminance'))->toStartWith($luminance)
        ->and($preview->filter('craft-chip.cvd-chip img')->attr('src'))->toEndWith("bachelor-$theme.jpg");
})->with([
    'light by default' => [null, 'light', '[[[0.389,'],
    'dark' => ['dark', 'dark', '[[[0.01,'],
]);

it('shows the thumb style setting for asset volumes and for fields as the thumb source, and thumb alignment unless cards are covered', function (string $type, bool $thumbField, ?string $style, bool $styleShown, bool $alignmentShown) {
    $layout = $type === Entry::class
        ? EntryModel::factory()->withField('images', Assets::class)->createElementWithFields()->element->getFieldLayout()
        : new FieldLayout(['type' => $type]);

    if ($thumbField) {
        $layout->thumbFieldKey = 'layoutElement:'.$layout->getCustomFieldElements()[0]->uid;
    }

    $layout->setCardThumbStyle($style);
    $designer = new Crawler('<div>'.app(CardDesigner::class)->html($layout).'</div>');
    $field = $designer->filter('craft-field[data-attribute="thumb-style"]');

    expect($field->filter('input[type="radio"][name="thumb-style"]')->each(fn (Crawler $radio) => $radio->attr('value')))->toBe(['normal', 'cover'])
        ->and(str_contains((string) $field->attr('class'), 'hidden'))->toBe(! $styleShown)
        ->and(str_contains((string) $designer->filter('craft-field[data-attribute="thumb-alignment"]')->attr('class'), 'hidden'))->toBe(! $alignmentShown);
})->with([
    'asset volume by default' => [Asset::class, false, null, true, false],
    'asset volume set to normal' => [Asset::class, false, 'normal', true, true],
    'user fields without a thumb source' => [User::class, false, 'cover', false, true],
    'entry type without a thumb source' => [Entry::class, false, 'cover', false, false],
    'entry type with a thumb source' => [Entry::class, true, null, true, true],
    'entry type with a thumb source set to cover' => [Entry::class, true, 'cover', true, false],
]);

it('gives only entry cards a header in the preview, for their entry type’s label', function (string $type, bool $header) {
    $card = new Crawler('<div>'.app(CardDesigner::class)->previewHtml(new FieldLayout(['type' => $type])).'</div>')->filter('craft-card');

    expect($card->filter('[slot="header"]')->count())->toBe($header ? 1 : 0);
})->with([
    'entry type' => [Entry::class, true],
    'asset volume' => [Asset::class, false],
    'user fields' => [User::class, false],
]);

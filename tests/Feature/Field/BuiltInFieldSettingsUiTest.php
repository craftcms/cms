<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Addresses;
use CraftCms\Cms\Field\Assets;
use CraftCms\Cms\Field\ButtonGroup;
use CraftCms\Cms\Field\Checkboxes;
use CraftCms\Cms\Field\Color;
use CraftCms\Cms\Field\ContentBlock;
use CraftCms\Cms\Field\Date;
use CraftCms\Cms\Field\Dropdown;
use CraftCms\Cms\Field\Email;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Icon;
use CraftCms\Cms\Field\Lightswitch;
use CraftCms\Cms\Field\Link;
use CraftCms\Cms\Field\Markdown;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Money;
use CraftCms\Cms\Field\MultiSelect;
use CraftCms\Cms\Field\Number;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Field\RadioButtons;
use CraftCms\Cms\Field\Range;
use CraftCms\Cms\Field\Table;
use CraftCms\Cms\Field\Time;
use CraftCms\Cms\Field\Users;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiResolver;
use Symfony\Component\DomCrawler\Crawler;

it('resolves and renders built-in field settings Forms', function (string $type) {
    $field = Fields::createField($type);
    $context = new UiContext(namespace: 'settings', refreshable: true);
    $form = $field->settingsUi($context);

    expect($form)->not->toBeNull();

    $payload = app(UiResolver::class)->resolve($form, $context);

    expect($payload->nodes)->not->toBeEmpty()
        ->and(json_encode($payload, JSON_THROW_ON_ERROR))->toBeString()
        ->and(app(UiHtmlRenderer::class)->render($payload))->toBeString();
})->with([
    'addresses' => Addresses::class,
    'assets' => Assets::class,
    'button group' => ButtonGroup::class,
    'checkboxes' => Checkboxes::class,
    'color' => Color::class,
    'content block' => ContentBlock::class,
    'date' => Date::class,
    'dropdown' => Dropdown::class,
    'email' => Email::class,
    'entries' => Entries::class,
    'icon' => Icon::class,
    'lightswitch' => Lightswitch::class,
    'link' => Link::class,
    'markdown' => Markdown::class,
    'matrix' => Matrix::class,
    'money' => Money::class,
    'multi-select' => MultiSelect::class,
    'number' => Number::class,
    'plain text' => PlainText::class,
    'radio buttons' => RadioButtons::class,
    'range' => Range::class,
    'table' => Table::class,
    'time' => Time::class,
    'users' => Users::class,
]);

it('preserves the rel advanced field label markup', function () {
    $field = Fields::createField(Link::class);
    $context = new UiContext(namespace: 'settings');
    $payload = app(UiResolver::class)->resolve($field->settingsUi($context), $context);
    $crawler = new Crawler(app(UiHtmlRenderer::class)->render($payload));
    $relOption = $crawler->filter('input[value="rel"]')->ancestors()->filter('craft-checkbox')->first();

    expect($relOption->filter('label code')->text())->toBe('rel');
});

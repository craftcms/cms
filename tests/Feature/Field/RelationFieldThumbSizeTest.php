<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Assets;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Ui\NodePayload;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiResolver;

/**
 * The resolved “Thumb Size” setting of an Assets field (the only relation
 * field with a Thumbs view mode), or `null` without one.
 *
 * @return array{path: list<string>, options: list<string>, value: mixed}|null
 */
function thumbSizeSetting(array $settings): ?array
{
    $context = new UiContext(namespace: 'settings');
    $field = Fields::createField(['type' => Assets::class, 'settings' => $settings]);
    $payload = app(UiResolver::class)->resolve($field->settingsUi($context), $context);
    $group = collect($payload->nodes)->firstWhere('uid', 'relation-thumb-size-settings');
    $setting = collect($group->children ?? [])->first(fn (NodePayload $node): bool => $node->control !== null);

    return $setting === null ? null : [
        'path' => array_slice($setting->control->path, 1),
        'options' => array_column($setting->control->props['options'], 'value'),
        'value' => data_get($payload->values, $setting->control->path),
    ];
}

it('offers the thumb sizes the view mode has', function (array $settings, array $expected) {
    expect(thumbSizeSetting($settings))->toBe($expected);
})->with([
    'list' => [['viewMode' => 'list'], ['path' => ['thumbSizes', 'list'], 'options' => ['sm', 'md', 'lg'], 'value' => 'sm']],
    'inline list' => [['viewMode' => 'list-inline', 'thumbSize' => 'md'], ['path' => ['thumbSizes', 'list'], 'options' => ['sm', 'md', 'lg'], 'value' => 'md']],
    'thumbs' => [['viewMode' => 'thumbs'], ['path' => ['thumbSizes', 'thumbs'], 'options' => ['sm', 'md', 'lg', 'xl', '2xl'], 'value' => 'lg']],
    'list ignores a thumbs-only size' => [['viewMode' => 'list', 'thumbSize' => 'xl'], ['path' => ['thumbSizes', 'list'], 'options' => ['sm', 'md', 'lg'], 'value' => 'sm']],
]);

it('hides the thumb size for card view modes', function (string $viewMode) {
    expect(thumbSizeSetting(['viewMode' => $viewMode]))->toBeNull();
})->with(['cards', 'cards-grid']);

it('saves the thumb size posted for the view mode’s family', function (string $viewMode, ?string $expected) {
    $field = Fields::createField(['type' => Assets::class, 'settings' => [
        'viewMode' => $viewMode,
        'thumbSizes' => ['list' => 'md', 'thumbs' => '2xl'],
    ]]);

    expect($field->thumbSize)->toBe($expected)
        ->and($field->getSettings())->toHaveKey('thumbSize', $expected)
        ->not->toHaveKey('thumbSizes');
})->with([
    'list' => ['list', 'md'],
    'inline list' => ['list-inline', 'md'],
    'thumbs' => ['thumbs', '2xl'],
    'cards' => ['cards', null],
]);

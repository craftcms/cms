<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Assets;
use CraftCms\Cms\Field\BaseRelationField;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Support\Facades\Fields;

/** @return array<string, mixed> The resolved `sources` control props. */
function sourcesProps(string $type, array $settings = []): array
{
    $field = Fields::createField(['type' => $type, 'settings' => $settings]);
    $context = new FormContext(namespace: 'settings');
    $payload = app(FormResolver::class)->resolve($field->settingsForm($context), $context);

    foreach ($payload->nodes as $node) {
        if ($node->control?->path === ['settings', 'sources']) {
            return $node->control->props;
        }
    }

    throw new RuntimeException('No sources control in the Form.');
}

it('gives the sources field an All option storing a single token', function (string $type) {
    $props = sourcesProps($type);

    expect($props)->toHaveKey('allLabel')
        ->and($props['allValue'])->toBe(BaseRelationField::ALL_SOURCES)
        ->and($props['allMode'])->toBe('singleValue');
})->with(['assets' => Assets::class, 'entries' => Entries::class]);

it('promotes an element type’s own “All” source to the All checkbox', function () {
    $props = sourcesProps(Entries::class);

    // Entries define an `*` source labelled "All entries"; it becomes the All
    // checkbox rather than rendering as a second option beside it.
    expect($props['allLabel'])->toBe('All entries')
        ->and(array_column($props['options'], 'value'))
        ->not->toContain(BaseRelationField::ALL_SOURCES);
});

it('falls back to “All” for element types with no such source', function () {
    // Assets sources are volumes only — no `*` entry to borrow a label from.
    expect(sourcesProps(Assets::class)['allLabel'])->toBe('All');
});

it('round-trips a field set to all sources', function (string $type) {
    // The stored value is the token, not an enumeration — so sources added
    // later stay included.
    $field = Fields::createField(['type' => $type, 'settings' => ['sources' => '*']]);

    expect($field->sources)->toBe(BaseRelationField::ALL_SOURCES);

    $context = new FormContext(namespace: 'settings');
    $payload = app(FormResolver::class)->resolve($field->settingsForm($context), $context);

    expect($payload->values['settings']['sources'])->toBe([BaseRelationField::ALL_SOURCES]);
})->with(['assets' => Assets::class, 'entries' => Entries::class]);

it('leaves single-source fields alone', function () {
    $field = Fields::createField(Assets::class);
    $field->allowMultipleSources = false;
    $context = new FormContext(namespace: 'settings');
    $payload = app(FormResolver::class)->resolve($field->settingsForm($context), $context);

    $source = collect($payload->nodes)
        ->first(fn ($node): bool => $node->control?->path === ['settings', 'source']);

    expect($source)->not->toBeNull()
        ->and($source->control->props)->not->toHaveKey('allLabel');
});

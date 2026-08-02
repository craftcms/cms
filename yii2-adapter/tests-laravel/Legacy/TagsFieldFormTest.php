<?php

declare(strict_types=1);

use craft\fields\Tags;

it('projects legacy Tags settings without the search-input option', function () {
    $field = new class extends Tags
    {
        public function getSourceOptions(): array
        {
            return [];
        }
    };
    $definition = $field->getSettingsForm(false)?->toArray();
    $names = collect($definition['elements'] ?? [])
        ->map(fn (array $element): ?string => $element['children'][0]['name'] ?? null)
        ->filter()
        ->values()
        ->all();

    expect($names)
        ->not->toContain('showSearchInput')
        ->toContain('source', 'selectionLabel');
});

<?php

declare(strict_types=1);

use CraftCms\Cms\Component\Contracts\ConfigurableComponentInterface;
use CraftCms\Cms\Dashboard\WidgetTypes;
use CraftCms\Cms\Field\FieldTypes;
use CraftCms\Cms\Field\Link;
use CraftCms\Cms\Filesystem\FilesystemTypes;

it('exposes only the native settings form contract', function () {
    expect(get_class_methods(ConfigurableComponentInterface::class))
        ->toContain('getSettingsFormDefinition')
        ->not->toContain('getSettingsHtml', 'getReadOnlySettingsHtml');
});

it('keeps legacy settings HTML methods out of core configurable types', function () {
    $types = [
        ...app(FieldTypes::class)->types(),
        ...app(FilesystemTypes::class)->types(),
        ...app(WidgetTypes::class)->types(),
        ...array_values(Link::types()),
    ];

    foreach ($types as $type) {
        expect(method_exists($type, 'getSettingsHtml'))
            ->toBeFalse(sprintf('%s::getSettingsHtml() remains in core.', $type))
            ->and(method_exists($type, 'getReadOnlySettingsHtml'))
            ->toBeFalse(sprintf('%s::getReadOnlySettingsHtml() remains in core.', $type));
    }
});

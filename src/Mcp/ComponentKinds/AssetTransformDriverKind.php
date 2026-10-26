<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\ComponentKinds;

use CraftCms\Cms\Asset\AssetTransformDrivers;
use CraftCms\Cms\Mcp\Schema\SettingsSchema;

/**
 * Asset transform drivers.
 *
 * @since 6.0.0
 */
readonly class AssetTransformDriverKind implements ComponentKind
{
    public function __construct(
        private AssetTransformDrivers $drivers,
        private SettingsSchema $settingsSchema,
    ) {}

    public static function handle(): string
    {
        return 'asset-transform-driver';
    }

    public function description(): string
    {
        return 'Asset transform drivers, and the image transform parameters they accept.';
    }

    public function usage(): string
    {
        return 'configuration.* with type: asset-transformers takes the handle as driver and its settings as settings. Image transforms take parametersSchema values in parameters, keyed by the asset transformer’s UID.';
    }

    public function scope(): ?string
    {
        return null;
    }

    public function types(?string $scope): array
    {
        return collect($this->drivers->definitions())
            ->map(static fn ($definition, string $handle): array => ['type' => $handle, 'name' => $definition->name])
            ->sortBy('name')
            ->values()
            ->all();
    }

    public function describe(string $type, ?string $scope): array
    {
        $definition = $this->drivers->definitions()[$type];

        return [
            'settingsSchema' => $this->settingsSchema->fromUi($definition->settingsFields),
            'parametersSchema' => $this->settingsSchema->fromUi(array_values($definition->parameterFields)),
        ];
    }
}

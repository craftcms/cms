<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Kinds;

use CraftCms\Cms\Asset\AssetTransformers;
use CraftCms\Cms\Ui\SettingsSchema;

/**
 * Asset transform drivers.
 *
 * @since 6.0.0
 */
readonly class AssetTransformDriverKind implements ComponentKind
{
    public function __construct(
        private AssetTransformers $transformers,
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
        return 'Disks choose a driver with the asset_transformer key in config/filesystems.php. Image transforms take parametersSchema values in parameters, keyed by driver name.';
    }

    public function scope(): ?string
    {
        return null;
    }

    public function types(?string $scope): array
    {
        return collect($this->transformers->definitions())
            ->map(static fn ($definition, string $name): array => ['type' => $name, 'name' => $name])
            ->sortBy('name')
            ->values()
            ->all();
    }

    public function describe(string $type, ?string $scope): array
    {
        $definition = $this->transformers->definitions()[$type];

        return [
            'parametersSchema' => $this->settingsSchema->fromUi(array_values($definition->parameterFields)),
        ];
    }
}

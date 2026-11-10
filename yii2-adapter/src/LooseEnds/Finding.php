<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\LooseEnds;

/**
 * Something in the project that still depends on the adapter, found by a Yii2 Loose Ends check.
 *
 * @internal
 */
readonly class Finding
{
    /**
     * @param string $key Identifies the kind of item.
     * @param string $title What depends on the adapter.
     * @param string $fix What to do to stop depending on it.
     * @param string|null $detail Supporting detail, such as the names of the settings involved.
     * @param string|null $file The project file involved, if any.
     * @param string|null $url A link with more information, such as a plugin’s changelog.
     * @param string|null $iconSvg An SVG icon for the item, such as a plugin’s icon.
     * @param PackageStatus|null $status For plugins and packages, whether a release that drops the adapter is
     * available. It’s shown instead of the severity.
     * @param array{handle: string, packageName: string, version: string}|null $update For plugins, the update that
     * drops the adapter, if it can be installed with the Craft version that’s installed.
     */
    public function __construct(
        public string $key,
        public Section $section,
        public Severity $severity,
        public string $title,
        public string $fix,
        public ?string $detail = null,
        public ?string $file = null,
        public ?int $line = null,
        public ?string $url = null,
        public ?string $urlLabel = null,
        public ?string $iconSvg = null,
        public ?PackageStatus $status = null,
        public ?array $update = null,
    ) {
    }
}

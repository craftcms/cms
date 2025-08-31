<?php

namespace CraftCms\Cms\License\Data;

use CraftCms\Cms\Shared\Enums\LicenseKeyStatus;

/**
 * @internal
 *
 * @since 6.0.0
 */
final readonly class LicenseData
{
    public LicenseKeyStatus $status;

    public function __construct(
        public bool $isCraft,
        public string|int $id,
        public string $handle,
        public string $name,
        /** @var string[] */
        public array $editions,
        public string $currentEdition,
        public string $currentEditionName,
        public string $licenseEdition,
        public string $licenseEditionName,
        public string $version,
        string|LicenseKeyStatus $status,
    ) {
        $this->status = LicenseKeyStatus::from($status);
    }

    public function isMultiEdition(): bool
    {
        return count($this->editions) > 1;
    }
}

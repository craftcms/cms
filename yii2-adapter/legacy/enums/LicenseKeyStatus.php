<?php

namespace craft\enums;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * LicenseKeyStatus defines all possible license key statuses for Craft and plugins.
     *
     * @since 5.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Shared\Enums\LicenseKeyStatus} instead.
     */
    enum LicenseKeyStatus: string
    {
    }
}

class_alias(\CraftCms\Cms\Shared\Enums\LicenseKeyStatus::class, LicenseKeyStatus::class);

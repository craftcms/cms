<?php

namespace craft\errors;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 3.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Plugin\Exceptions\InvalidLicenseKeyException} instead.
     */
    class InvalidLicenseKeyException extends \CraftCms\Cms\Plugin\Exceptions\InvalidLicenseKeyException
    {
    }
}

class_alias(\CraftCms\Cms\Plugin\Exceptions\InvalidLicenseKeyException::class, InvalidLicenseKeyException::class);

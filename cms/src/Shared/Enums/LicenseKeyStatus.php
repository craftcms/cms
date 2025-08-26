<?php

namespace CraftCms\Cms\Shared\Enums;

/**
 * LicenseKeyStatus defines all possible license key statuses for Craft and plugins.
 *
 * @since 6.0.0
 */
enum LicenseKeyStatus: string
{
    case Valid = 'valid';
    case Trial = 'trial';
    case Invalid = 'invalid';
    case Mismatched = 'mismatched';
    case Astray = 'astray';
    case Unknown = 'unknown';
}

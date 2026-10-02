<?php

declare(strict_types=1);

namespace CraftCms\Cms\Shared\Enums;

/**
 * LicenseKeyStatus defines all possible license key statuses for Craft and plugins.
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

<?php

namespace craft\auth\methods;

/**
 * Time-based one-time password authentication method.
 *
 * @since 5.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Auth\Methods\TOTP} instead.
 */
class TOTP extends \CraftCms\Cms\Auth\Methods\TOTP
{
    use \craft\base\LegacyEventConstants;
}

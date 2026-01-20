<?php

namespace craft\auth\methods;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * Time-based one-time password authentication method.
     *
     * @since 5.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Auth\Methods\TOTP} instead.
     */
    class TOTP
    {
    }
}

class_alias(\CraftCms\Cms\Auth\Methods\TOTP::class, TOTP::class);

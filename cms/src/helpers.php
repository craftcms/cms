<?php

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Support\Str;

/** @since 6.0.0 */
function cp_url(string $url)
{
    return Str::start($url, Str::finish(app(GeneralConfig::class)->cpTrigger, '/'));
}

/** @since 6.0.0 */
function cp_redirect(string $url, int $status = 302, array $headers = [], ?bool $secure = null)
{
    return redirect(
        to: cp_url($url),
        status: $status,
        headers: $headers,
        secure: $secure
    );
}

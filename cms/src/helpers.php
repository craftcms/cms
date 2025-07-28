<?php

use CraftCms\Cms\Support\Str;

function cp_url(string $url)
{
    return Str::start($url, Str::finish(config('craft.general.cpTrigger', 'admin'), '/'));
}

function cp_redirect(string $url, int $status = 302, array $headers = [], ?bool $secure = null)
{
    return redirect(
        to: cp_url($url),
        status: $status,
        headers: $headers,
        secure: $secure
    );
}

<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\OAuth\Contracts;

use CraftCms\Cms\Auth\OAuth\Data\ButtonData;
use Illuminate\Support\HtmlString;

interface RendersOAuthButton
{
    public function handle(ButtonData $button): HtmlString;
}

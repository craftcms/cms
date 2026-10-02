<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\OAuth;

use CraftCms\Cms\Auth\OAuth\Contracts\RendersOAuthButton;
use CraftCms\Cms\Auth\OAuth\Data\ButtonData;
use Illuminate\Support\HtmlString;

class CustomButtonRenderer implements RendersOAuthButton
{
    public function handle(ButtonData $button): HtmlString
    {
        return new HtmlString(sprintf(
            '<div class="oauth-custom" data-provider="%s"><a href="%s">%s</a></div>',
            $button->provider->handle,
            $button->url,
            $button->label,
        ));
    }
}

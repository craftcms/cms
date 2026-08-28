<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\OAuth\Actions;

use CraftCms\Cms\Auth\OAuth\Contracts\RendersOAuthButton;
use CraftCms\Cms\Auth\OAuth\Data\ButtonData;
use CraftCms\Cms\Support\Html;
use Illuminate\Support\HtmlString;

class ButtonRenderer implements RendersOAuthButton
{
    public function handle(ButtonData $button): HtmlString
    {
        return new HtmlString(Html::tag('craft-button', Html::encode($button->label), [
            'href' => $button->url,
            'class' => 'cp:w-full',
            'data-provider' => $button->provider->handle,
        ]));
    }
}

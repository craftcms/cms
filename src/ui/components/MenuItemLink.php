<?php

namespace craft\ui\components;

use craft\enums\MenuItemType;
use craft\helpers\UrlHelper;

class MenuItemLink extends MenuItem
{
    protected string $url;

    protected MenuItemType $type = MenuItemType::Link;

    public function getAttributes(): array
    {
        return array_merge_recursive(parent::getAttributes(), [
            'href' => $this->getUrl(),
        ]);
    }

    public function getUrl(): string
    {
        $url = $this->evaluate($this->url);
        if (UrlHelper::isFullUrl($url)) {
            return $url;
        }

        return UrlHelper::url($url);
    }

    public function url(string $url): static
    {
        $this->url = $url;
        return $this;
    }
}

<?php

namespace craft\ui\components;

use Craft;
use craft\helpers\Cp;
use craft\helpers\UrlHelper;
use craft\ui\concerns\HasId;
use craft\ui\concerns\HasLabel;
use Illuminate\Support\Collection;
use Twig\Markup;

class NavItem
{
    use HasLabel;
    use HasId;

    protected string|Icon|null $icon = null;
    protected ?int $badgeCount = null;
    protected array $items = [];
    protected bool $external = false;
    protected ?string $url = null;
    protected bool $selected = false;
    protected ?string $path = null;
    protected string $type = 'default';

    public function __construct()
    {
    }

    public static function make(): self
    {
        return new self();
    }

    public function getId(): ?string
    {
        if ($this->id) {
            return $this->id;
        }

        if ($this->path) {
            return $this->path;
        }

        return null;
    }

    public function withProps(array $properties): self
    {
        foreach ($properties as $key => $value) {
            $this->{$key}($value);
        }

        return $this;
    }

    public function badgeCount(?int $count): self
    {
        $this->badgeCount = $count;
        return $this;
    }

    public function getBadgeCount(): ?int
    {
        return $this->badgeCount;
    }

    public function url(?string $value): self
    {
        $this->url = $value;
        return $this;
    }

    public function getUrl(): ?string
    {
        if (!$this->url) {
            if ($this->path) {
                return UrlHelper::url($this->path);
            }

            return null;
        }

        return $this->url;
    }


    public function icon(string|Icon $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    public function getIcon(): ?Icon
    {
        if (is_string($this->icon)) {
            return Icon::make()
                ->icon($this->icon)
                ->label($this->getLabel());
        } elseif ($this->icon instanceof Icon) {
            return $this->icon;
        }

        return null;
    }

    public function external(bool $value = true): self
    {
        $this->external = $value;
        return $this;
    }

    public function getExternal(): bool
    {
        return $this->external;
    }

    public function items(array $items): self
    {
        $this->items = $items;
        return $this;
    }

    public function getItems(): Collection
    {
        return Collection::make($this->items)
            ->map(function($item) {
                if ($item instanceof NavItem) {
                    return $item->render();
                }

                return NavItem::make()->render();
            });
    }

    public function selected(bool $value = true): self
    {
        $this->selected = $value;
        return $this;
    }

    public function getSelected(): bool
    {
        return $this->selected;
    }

    public function path(?string $value): self
    {
        $this->path = $value;
        return $this;
    }

    public function getPath(): ?string
    {
        if ($this->getUrl()) {
            $cpTrigger = Craft::$app->getConfig()->getGeneral()->cpTrigger;
            return substr(parse_url($this->getUrl(), PHP_URL_PATH), strlen($cpTrigger) + 2);
        }
        return $this->path;
    }

    public function type(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function render(): string
    {
        return Cp::renderTemplate('_ui/nav-item.twig', [
            'icon' => $this->getIcon() ? new Markup($this->getIcon()->render(), 'UTF-8') : null,
            'label' => $this->getLabel(),
            'badgeCount' => $this->getBadgeCount(),
            'url' => $this->getUrl(),
            'id' => $this->getId(),
            'external' => $this->getExternal(),
            'items' => $this->getItems(),
            'selected' => $this->getSelected(),
            'type' => $this->getType(),
        ]);
    }
}

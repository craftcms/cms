<?php

namespace craft\ui\components;

use Craft;
use craft\helpers\UrlHelper;
use craft\ui\Component;
use craft\ui\concerns\HasId;
use craft\ui\concerns\HasLabel;
use Illuminate\Support\Collection;

class NavItem extends Component
{
    use HasLabel;
    use HasId;

    protected string $view = '_ui/nav-item.twig';

    protected string|Icon|null $icon = null;
    protected ?int $badgeCount = null;
    protected array $items = [];
    protected bool $external = false;
    protected ?string $url = null;
    protected bool $selected = false;
    protected ?string $path = null;
    protected string $type = 'default';

    public function getId(): ?string
    {
        return $this->getPath() ?? $this->getId();
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

    public function getIcon(): ?string
    {
        $icon = $this->icon;

        if (is_string($icon)) {
            $icon = Icon::make()
                ->icon($this->icon)
                ->label($this->getLabel());
        }

        if (!$icon) {
            return null;
        }

        return $icon->render();
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

    public function getAttributes(): array
    {
        return array_merge_recursive(parent::getAttributes(), [
            'class' => array_filter([
                $this->getType() === 'heading' ? 'heading' : null,
            ]),
        ]);
    }
}

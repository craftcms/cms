<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Data;

use CraftCms\Cms\Component\Component;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;

class NavItem extends Component
{
    public string $label;

    public ?string $ariaLabel = null;

    public string $url;

    public ?string $icon = null;

    public ?string $id = null;

    public ?string $fontIcon = null;

    public int $badgeCount = 0;

    public bool $external = false;

    /** @var NavItem[]|false */
    #[LiteralTypeScriptType('CraftCms.Cms.Cp.Data.NavItem[] | false')]
    public array|false $subnav = false;

    public bool $selected = false;

    /**
     * When true, the list will render as a non-collapsible semantic group
     */
    public bool $group = false;

    /** @var array<string, mixed> */
    public array $linkAttributes = [];

    public function __construct(object|array $config = [])
    {
        if (isset($config['subnav']) && is_array($config['subnav'])) {
            $config['subnav'] = array_map(
                fn ($item) => $item instanceof self ? $item : new self($item),
                $config['subnav'],
            );
        }

        parent::__construct($config);
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function ariaLabel(?string $ariaLabel): self
    {
        $this->ariaLabel = $ariaLabel;

        return $this;
    }

    public function url(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function icon(?string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function id(?string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function fontIcon(?string $fontIcon): self
    {
        $this->fontIcon = $fontIcon;

        return $this;
    }

    public function badgeCount(int $badgeCount): self
    {
        $this->badgeCount = $badgeCount;

        return $this;
    }

    public function external(bool $external): self
    {
        $this->external = $external;

        return $this;
    }

    /** @param NavItem[]|false $subnav */
    public function subnav(array|false $subnav): self
    {
        $this->subnav = $subnav;

        return $this;
    }

    public function add(NavItem $item): self
    {
        if ($this->subnav === false) {
            $this->subnav = [];
        }

        $this->subnav[] = $item;

        return $this;
    }

    public function selected(bool $selected): self
    {
        $this->selected = $selected;

        return $this;
    }

    public function group(bool $group): self
    {
        $this->group = $group;

        return $this;
    }

    /** @param array<string, mixed> $linkAttributes */
    public function linkAttributes(array $linkAttributes): self
    {
        $this->linkAttributes = $linkAttributes;

        return $this;
    }
}

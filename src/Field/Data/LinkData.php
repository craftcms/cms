<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Data;

use craft\base\ElementInterface;
use craft\base\Serializable;
use craft\helpers\Template;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Field\LinkTypes\BaseElementLinkType;
use CraftCms\Cms\Field\LinkTypes\BaseLinkType;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;
use Stringable;
use Twig\Markup;

#[AllowedInSandbox]
class LinkData implements Serializable, Stringable
{
    /** @var string|null The link’s URL suffix value. */
    public ?string $urlSuffix = null;

    /** @var string|null The link’s `target` attribute. */
    public ?string $target = null;

    /** @var string|null The link’s `title` attribute. */
    public ?string $title = null;

    /** @var string|null The link’s `class` attribute. */
    public ?string $class = null;

    /** @var string|null The link’s `id` attribute. */
    public ?string $id = null;

    /** @var string|null The link’s `rel` attribute. */
    public ?string $rel = null;

    /** @var string|null The link’s `aria-label` attribute. */
    public ?string $ariaLabel = null;

    /** @var bool Whether the link should have a `download` attribute. */
    public bool $download = false;

    private string $renderedValue;

    private ?string $label = null;

    private ?string $filename = null;

    public function __construct(
        private readonly string $value,
        private readonly BaseLinkType $linkType,
    ) {}

    public function __toString(): string
    {
        return $this->getUrl();
    }

    /**
     * Returns the link type ID.
     */
    public function getType(): string
    {
        return $this->linkType::id();
    }

    /**
     * Returns the link value.
     */
    public function getValue(): string
    {
        if (! isset($this->renderedValue)) {
            $this->renderedValue = $this->linkType->renderValue($this->value);
        }

        return $this->renderedValue;
    }

    /**
     * Returns the full link URL.
     */
    public function getUrl(): string
    {
        $url = $this->getValue();

        if ($url === '') {
            return $url;
        }

        return sprintf('%s%s', $url, $this->urlSuffix ?? '');
    }

    /**
     * Returns the link label.
     *
     * @param  bool|null  $custom  Whether to return the custom label
     */
    public function getLabel(?bool $custom = null): ?string
    {
        if ($custom || (isset($this->label) && $custom === null)) {
            return $this->label;
        }

        return $this->linkType->linkLabel($this->value);
    }

    /**
     * Sets the link label.
     */
    public function setLabel(?string $label): void
    {
        $this->label = $label;
    }

    /**
     * Returns the download filename.
     *
     * @param  bool  $custom  Whether to return the custom filename
     */
    public function getFilename(bool $custom = true): ?string
    {
        return $custom ? $this->filename : $this->linkType->filename($this->value);
    }

    /**
     * Sets the download filename.
     */
    public function setFilename(?string $filename): void
    {
        $this->filename = $filename;
    }

    /**
     * Returns an anchor tag for this link.
     */
    public function getLink(): Markup
    {
        $attributes = $this->getAttributes();

        if ($attributes === null) {
            $html = '';
        } else {
            $label = $this->getLabel();
            if ($label === '') {
                $label = $this->getUrl();
            }
            $html = Html::a(Html::encode($label), options: $attributes);
        }

        return Template::raw($html);
    }

    /**
     * Returns the attributes that should be added to `<a>` tags for this link.
     */
    public function getAttributes(): ?array
    {
        $url = $this->getUrl();

        if ($url === '') {
            return null;
        }

        return [
            'href' => $url,
            'target' => $this->target,
            'title' => $this->title,
            'class' => $this->class,
            'id' => $this->id,
            'rel' => $this->rel,
            'aria' => [
                'label' => $this->ariaLabel,
            ],
            'download' => $this->download && (bool) ($this->filename ?? true),
        ];
    }

    /**
     * Returns an element query that will fetch the element linked by the field, if there is one.
     */
    public function getElementQuery(): ?ElementQueryInterface
    {
        if (! $this->linkType instanceof BaseElementLinkType) {
            return null;
        }

        return $this->linkType->elementQuery($this->value);
    }

    /**
     * Returns the element linked by the field, if there is one.
     */
    public function getElement(): ?ElementInterface
    {
        if (! $this->linkType instanceof BaseElementLinkType) {
            return null;
        }

        return $this->linkType->element($this->value);
    }

    public function serialize(): array
    {
        return array_filter([
            'value' => $this->value,
            'type' => $this->getType(),
            'label' => $this->label,
            'urlSuffix' => $this->urlSuffix,
            'target' => $this->target,
            'title' => $this->title,
            'class' => $this->class,
            'id' => $this->id,
            'rel' => $this->rel,
            'ariaLabel' => $this->ariaLabel,
            'download' => $this->download,
            'filename' => $this->filename,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Data;

use Closure;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\LinkTypes\BaseElementLinkType;
use CraftCms\Cms\Field\LinkTypes\BaseLinkType;
use CraftCms\Cms\Shared\Contracts\Serializable;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Template;
use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;
use Illuminate\Contracts\Support\Arrayable;
use Stringable;
use Twig\Markup;

/**
 * @implements Arrayable<string, mixed>
 *
 * @since 6.0.0
 */
class LinkData implements Arrayable, Serializable, Stringable
{
    /** @var string|null The link’s URL suffix value. */
    #[AllowedInSandbox]
    public ?string $urlSuffix = null;

    /** @var string|null The link’s `target` attribute. */
    #[AllowedInSandbox]
    public ?string $target = null;

    /** @var string|null The link’s `title` attribute. */
    #[AllowedInSandbox]
    public ?string $title = null;

    /** @var string|null The link’s `class` attribute. */
    #[AllowedInSandbox]
    public ?string $class = null;

    /** @var string|null The link’s `id` attribute. */
    #[AllowedInSandbox]
    public ?string $id = null;

    /** @var string|null The link’s `rel` attribute. */
    #[AllowedInSandbox]
    public ?string $rel = null;

    /** @var string|null The link’s `aria-label` attribute. */
    #[AllowedInSandbox]
    public ?string $ariaLabel = null;

    /** @var bool Whether the link should have a `download` attribute. */
    #[AllowedInSandbox]
    public bool $download = false;

    private string $renderedValue;

    private ?string $label = null;

    private ?string $filename = null;

    private ?LinkElementLoader $elementLoader = null;

    private ?Closure $eagerLoader = null;

    private ?ElementInterface $eagerLoadedElement = null;

    public function __construct(
        private readonly string $value,
        private readonly BaseLinkType $linkType,
    ) {}

    /**
     * Leaves out the eager loader, which can’t be serialized, so links can be cached before they’re read.
     *
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        $data = get_object_vars($this);
        unset($data['eagerLoader']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function __unserialize(array $data): void
    {
        foreach ($data as $name => $value) {
            $this->$name = $value;
        }
    }

    public function __toString(): string
    {
        return $this->getUrl();
    }

    /**
     * Returns the link type ID.
     */
    #[AllowedInSandbox]
    public function getType(): string
    {
        return $this->linkType::id();
    }

    /**
     * Returns the link value.
     */
    #[AllowedInSandbox]
    public function getValue(): string
    {
        if (! isset($this->renderedValue)) {
            $this->loadElement();
            $this->renderedValue = $this->linkType->renderValue($this->value);
        }

        return $this->renderedValue;
    }

    /**
     * Returns the full link URL.
     *
     * @param  bool  $anyStatus  Whether to return a value regardless of the linked element’s status
     */
    #[AllowedInSandbox]
    public function getUrl(bool $anyStatus = true): string
    {
        $url = $this->getValue();

        if ($url === '') {
            return $url;
        }

        if (! $anyStatus) {
            $status = $this->getElement()?->getStatus();
            if ($status && ! in_array($status, [Element::STATUS_ENABLED, Entry::STATUS_LIVE])) {
                return '';
            }
        }

        return sprintf('%s%s', $url, $this->urlSuffix ?? '');
    }

    /**
     * Returns the link label.
     *
     * @param  bool|null  $custom  Whether to return the custom label
     */
    #[AllowedInSandbox]
    public function getLabel(?bool $custom = null): ?string
    {
        if ($custom || (isset($this->label) && $custom === null)) {
            return $this->label;
        }

        $this->loadElement();

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
    #[AllowedInSandbox]
    public function getFilename(bool $custom = true): ?string
    {
        if ($custom) {
            return $this->filename;
        }

        $this->loadElement();

        return $this->linkType->filename($this->value);
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
    #[AllowedInSandbox]
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
     *
     * @return array{href: string, target: string|null, title: string|null, class: string|null, id: string|null, rel: string|null, aria: array{label: string|null}, download: bool|string}|null
     */
    #[AllowedInSandbox]
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
            'download' => $this->download && $this->filename !== '' ? ($this->filename ?? true) : false,
        ];
    }

    /**
     * Returns an element query that will fetch the element linked by the field, if there is one.
     */
    #[AllowedInSandbox]
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
    #[AllowedInSandbox]
    public function getElement(): ?ElementInterface
    {
        if (! $this->linkType instanceof BaseElementLinkType) {
            return null;
        }

        $this->loadElement();

        return $this->eagerLoadedElement ?? $this->linkType->element($this->value);
    }

    /**
     * Sets the loader that fetches this link’s element together with other links’ elements.
     *
     * @internal
     */
    public function setElementLoader(LinkElementLoader $loader): void
    {
        $this->elementLoader = $loader;
    }

    /**
     * Sets the callback that eager-loads this link’s element, together with the elements of the same field on other
     * elements, the first time it’s needed.
     *
     * @internal
     */
    public function setEagerLoader(Closure $eagerLoader): void
    {
        $this->eagerLoader = $eagerLoader;
    }

    /**
     * Sets the element that was eager-loaded for this link.
     *
     * @internal
     */
    public function setEagerLoadedElement(ElementInterface $element): void
    {
        $this->eagerLoader = null;
        $this->eagerLoadedElement = $element;
        BaseElementLinkType::rememberElement($this->value, $element);
    }

    private function loadElement(): void
    {
        if ($this->eagerLoader !== null) {
            $eagerLoader = $this->eagerLoader;
            $this->eagerLoader = null;

            if (! BaseElementLinkType::hasFetchedElement($this->value)) {
                $eagerLoader();
            }
        }

        $this->elementLoader?->load();
    }

    /** @return array{type: string, value?: string, label?: string, urlSuffix?: string, target?: string, title?: string, class?: string, id?: string, rel?: string, ariaLabel?: string, download?: true, filename?: string} */
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

    public function toArray(): array
    {
        return [
            'type' => $this->getType(),
            'value' => $this->getValue(),
            'url' => $this->getUrl(),
            'label' => $this->getLabel(),
            'filename' => $this->getFilename(),
            'link' => (string) $this->getLink(),
            'attributes' => $this->getAttributes(),
            'defaultLabel' => $this->getLabel(false),
            'urlSuffix' => $this->urlSuffix,
            'target' => $this->target,
            'title' => $this->title,
            'class' => $this->class,
            'id' => $this->id,
            'rel' => $this->rel,
            'ariaLabel' => $this->ariaLabel,
            'download' => $this->download,
            'elementType' => $this->getElement() !== null ? $this->getElement()::class : null,
            'elementId' => $this->getElement()?->id,
            'elementSiteId' => $this->getElement()?->siteId,
            'elementTitle' => $this->getElement() !== null ? (string) $this->getElement() : null,
        ];
    }
}

<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields\data;

use Closure;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\Serializable;
use craft\elements\db\ElementQueryInterface;
use craft\elements\Entry;
use craft\fields\linktypes\BaseElementLinkType;
use craft\fields\linktypes\BaseLinkType;
use craft\helpers\Html;
use craft\helpers\Template;
use craft\web\twig\AllowedInSandbox;
use Twig\Markup;
use yii\base\Arrayable;
use yii\base\ArrayableTrait;
use yii\base\BaseObject;

/**
 * Link field data class.
 *
 * @property-read ElementInterface|null $element The element linked by the field, if there is one
 * @property-read ElementQueryInterface|null $elementQuery An element query that will fetch the element linked by the field, if there is one
 * @property-read Markup $link An anchor tag for this link
 * @property-read array|null $attributes The attributes that should be added to `<a>` tags for this link
 * @property-read string $label The link label
 * @property-read string $type The link type ID
 * @property-read string $url The full link URL, including the suffix
 * @property-read string $value The link value
 * @property-read string|null $filename The download filename
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.3.0
 */
class LinkData extends BaseObject implements Serializable, Arrayable
{
    use ArrayableTrait {
        fields as traitFields;
    }

    /**
     * @var string|null The link’s URL suffix value.
     * @since 5.6.0
     */
    #[AllowedInSandbox]
    public ?string $urlSuffix = null;

    /**
     * @var string|null The link’s `target` attribute.
     * @since 5.5.0
     */
    #[AllowedInSandbox]
    public ?string $target = null;

    /**
     * @var string|null The link’s `title` attribute.
     * @since 5.6.0
     */
    #[AllowedInSandbox]
    public ?string $title = null;

    /**
     * @var string|null The link’s `class` attribute.
     * @since 5.6.0
     */
    #[AllowedInSandbox]
    public ?string $class = null;

    /**
     * @var string|null The link’s `id` attribute.
     * @since 5.6.0
     */
    #[AllowedInSandbox]
    public ?string $id = null;

    /**
     * @var string|null The link’s `rel` attribute.
     * @since 5.6.0
     */
    #[AllowedInSandbox]
    public ?string $rel = null;

    /**
     * @var string|null The link’s `aria-label` attribute.
     * @since 5.6.0
     */
    #[AllowedInSandbox]
    public ?string $ariaLabel = null;

    /**
     * @var bool Whether the link should have a `download` attribute.
     * @since 5.7.0
     */
    #[AllowedInSandbox]
    public bool $download = false;

    private string $renderedValue;
    private ?string $label = null;
    private ?string $filename = null;

    /**
     * @var LinkEagerLoadState|null Eager loader and eager-loaded element, shared with clones of the link
     */
    private ?LinkEagerLoadState $eagerLoadState = null;

    public function __construct(
        private readonly string $value,
        private readonly BaseLinkType $linkType,
        array $config = [],
    ) {
        parent::__construct($config);
    }

    public function __toString(): string
    {
        return $this->getUrl();
    }

    /**
     * @inheritdoc
     */
    public function fields(): array
    {
        $fields = $this->traitFields();

        $fields['type'] = 'type';
        $fields['value'] = 'value';
        $fields['url'] = 'url';
        $fields['label'] = 'label';
        $fields['filename'] = 'filename';
        $fields['link'] = 'link';
        $fields['attributes'] = 'attributes';
        $fields['defaultLabel'] = fn() => $this->getLabel(false);
        $fields['elementType'] = fn() => $this->getElement() ? $this->getElement()::class : null;
        $fields['elementId'] = fn() => $this->getElement()?->id;
        $fields['elementSiteId'] = fn() => $this->getElement()?->siteId;
        $fields['elementTitle'] = fn() => $this->getElement() ? (string)$this->getElement() : null;

        return $fields;
    }

    /**
     * Returns the link type ID.
     *
     * @return string
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
        if (!isset($this->renderedValue)) {
            $this->loadElement();
            $this->renderedValue = $this->linkType->renderValue($this->value);
        }
        return $this->renderedValue;
    }

    /**
     * Returns the full link URL.
     *
     * @param bool $anyStatus Whether to return a value regardless of the linked element’s status
     * @since 5.6.0
     */
    #[AllowedInSandbox]
    public function getUrl(bool $anyStatus = true): string
    {
        $url = $this->getValue();

        if ($url === '') {
            return $url;
        }

        if (!$anyStatus) {
            $status = $this->getElement()?->getStatus();
            if ($status && !in_array($status, [Element::STATUS_ENABLED, Entry::STATUS_LIVE])) {
                return '';
            }
        }

        return sprintf('%s%s', $url, $this->urlSuffix ?? '');
    }

    /**
     * Returns the link label.
     *
     * @param bool|null $custom Whether to return the custom label
     * @return string|null
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
     *
     * @param string|null $label
     * @since 5.5.0
     */
    public function setLabel(?string $label): void
    {
        $this->label = $label;
    }

    /**
     * Returns the download filename.
     *
     * @param bool $custom Whether to return the custom filename
     * @return string|null
     * @since 5.7.0
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
     *
     * @param string|null $filename
     * @since 5.7.0
     */
    public function setFilename(?string $filename): void
    {
        $this->filename = $filename;
    }

    /**
     * Returns an anchor tag for this link.
     *
     * @return Markup
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
     * @return array|null
     * @since 5.9.0
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
            'download' => $this->download ? ($this->filename ?? true) : false,
        ];
    }

    /**
     * Returns an element query that will fetch the element linked by the field, if there is one.
     *
     * @return ElementQueryInterface|null
     * @since 5.6.0
     */
    #[AllowedInSandbox]
    public function getElementQuery(): ?ElementQueryInterface
    {
        if (!$this->linkType instanceof BaseElementLinkType) {
            return null;
        }
        return $this->linkType->elementQuery($this->value);
    }

    /**
     * Returns the element linked by the field, if there is one.
     *
     * @return ElementInterface|null
     */
    #[AllowedInSandbox]
    public function getElement(): ?ElementInterface
    {
        if (!$this->linkType instanceof BaseElementLinkType) {
            return null;
        }

        $this->loadElement();

        return $this->eagerLoadState->element ?? $this->linkType->element($this->value);
    }

    /**
     * Sets the callback that eager-loads this link’s element, together with the elements of the same field on other
     * elements, the first time it’s needed.
     *
     * @param Closure $eagerLoader
     * @internal
     */
    public function setEagerLoader(Closure $eagerLoader): void
    {
        $this->eagerLoadState()->loader = $eagerLoader;
    }

    /**
     * Sets the element that was eager-loaded for this link.
     *
     * @param ElementInterface $element
     * @internal
     */
    public function setEagerLoadedElement(ElementInterface $element): void
    {
        $state = $this->eagerLoadState();
        $state->loader = null;
        $state->element = $element;

        if ($this->linkType instanceof BaseElementLinkType) {
            $this->linkType::rememberElement($this->value, $element);
        }
    }

    private function eagerLoadState(): LinkEagerLoadState
    {
        return $this->eagerLoadState ??= new LinkEagerLoadState();
    }

    private function loadElement(): void
    {
        $eagerLoader = $this->eagerLoadState?->loader;

        if ($eagerLoader !== null) {
            $this->eagerLoadState->loader = null;

            if ($this->linkType instanceof BaseElementLinkType && !$this->linkType::hasFetchedElement($this->value)) {
                $eagerLoader();
            }
        }
    }

    public function serialize(): mixed
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

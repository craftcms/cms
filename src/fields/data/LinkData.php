<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields\data;

use craft\base\ElementInterface;
use craft\base\Serializable;
use craft\elements\db\ElementQueryInterface;
use craft\fields\linktypes\BaseElementLinkType;
use craft\fields\linktypes\BaseLinkType;
use craft\helpers\Html;
use craft\helpers\Template;
use craft\web\twig\AllowedInSandbox;
use Twig\Markup;
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
#[AllowedInSandbox]
class LinkData extends BaseObject implements Serializable
{
    /**
     * @var string|null The link’s URL suffix value.
     * @since 5.6.0
     */
    public ?string $urlSuffix = null;

    /**
     * @var string|null The link’s `target` attribute.
     * @since 5.5.0
     */
    public ?string $target = null;

    /**
     * @var string|null The link’s `title` attribute.
     * @since 5.6.0
     */
    public ?string $title = null;

    /**
     * @var string|null The link’s `class` attribute.
     * @since 5.6.0
     */
    public ?string $class = null;

    /**
     * @var string|null The link’s `id` attribute.
     * @since 5.6.0
     */
    public ?string $id = null;

    /**
     * @var string|null The link’s `rel` attribute.
     * @since 5.6.0
     */
    public ?string $rel = null;

    /**
     * @var string|null The link’s `aria-label` attribute.
     * @since 5.6.0
     */
    public ?string $ariaLabel = null;

    /**
     * @var bool Whether the link should have a `download` attribute.
     * @since 5.7.0
     */
    public bool $download = false;

    private string $renderedValue;
    private ?string $label = null;
    private ?string $filename = null;

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
     * Returns the link type ID.
     *
     * @return string
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
        if (!isset($this->renderedValue)) {
            $this->renderedValue = $this->linkType->renderValue($this->value);
        }
        return $this->renderedValue;
    }

    /**
     * Returns the full link URL.
     *
     * @since 5.6.0
     */
    public function getUrl(): string
    {
        return sprintf('%s%s', $this->getValue(), $this->urlSuffix ?? '');
    }

    /**
     * Returns the link label.
     *
     * @param bool|null $custom Whether to return the custom label
     * @return string|null
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
    public function getFilename(bool $custom = true): ?string
    {
        return $custom ? $this->filename : $this->linkType->filename($this->value);
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
    public function getElement(): ?ElementInterface
    {
        if (!$this->linkType instanceof BaseElementLinkType) {
            return null;
        }
        return $this->linkType->element($this->value);
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

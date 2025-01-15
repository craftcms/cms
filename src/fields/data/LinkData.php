<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields\data;

use craft\base\ElementInterface;
use craft\base\Serializable;
use craft\fields\linktypes\BaseElementLinkType;
use craft\fields\linktypes\BaseLinkType;
use craft\helpers\Html;
use craft\helpers\Template;
use Twig\Markup;
use yii\base\BaseObject;

/**
 * Link field data class.
 *
 * @property-read string $type The link type ID
 * @property-read string $value The link value
 * @property-read string $label The link label
 * @property-read Markup|null $link An anchor tag for this link
 * @property-read ElementInterface|null $element The element linked by the field, if there is one
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.3.0
 */
class LinkData extends BaseObject implements Serializable
{
    /**
     * @var string|null The link’s `target` attribute.
     * @since 5.5.0
     */
    public ?string $target = null;

    private string $renderedValue;
    private ?string $label = null;

    public function __construct(
        private readonly string $value,
        private readonly BaseLinkType $linkType,
        array $config = [],
    ) {
        parent::__construct($config);
    }

    public function __toString(): string
    {
        return $this->getValue();
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
     * Returns an anchor tag for this link.
     *
     * @return Markup
     */
    public function getLink(): Markup
    {
        $url = $this->getValue();
        if ($url === '') {
            $html = '';
        } else {
            $label = $this->getLabel();
            $html = Html::a(Html::encode($label !== '' ? $label : $url), $url, [
                'target' => $this->target,
            ]);
        }

        return Template::raw($html);
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
        return [
            'value' => $this->value,
            'type' => $this->getType(),
            'label' => $this->label,
            'target' => $this->target,
        ];
    }
}

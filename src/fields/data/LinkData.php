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
    private string $renderedValue;

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
     * @return string
     */
    public function getLabel(): string
    {
        return $this->linkType->linkLabel($this->value);
    }

    /**
     * Returns an anchor tag for this link.
     *
     * @return Markup|null
     */
    public function getLink(): ?Markup
    {
        $url = $this->getValue();
        if ($url === '') {
            $html = '';
        } else {
            $label = $this->getLabel();
            $html = Html::a(Html::encode($label !== '' ? $label : $url), $url);
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
        return $this->value;
    }
}

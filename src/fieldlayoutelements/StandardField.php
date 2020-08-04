<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements;

use craft\base\ElementInterface;
use craft\helpers\ArrayHelper;

/**
 * StandardField is the base class for standard fields that can be included in field layouts.
 *
 * Standard fields can be registered using [[\craft\models\FieldLayout::EVENT_DEFINE_STANDARD_FIELDS]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
abstract class StandardField extends BaseField
{
    /**
     * @var bool Whether the field *must* be present within the layout.
     */
    public $mandatory = false;

    /**
     * @var bool Whether the field can optionally be marked as required.
     */
    public $requirable = false;

    /**
     * @var string the element attribute this field is for.
     */
    public $attribute;

    /**
     * @var string|null The input’s `id` attribute value.
     *
     * If this is not set, [[attribute()]] will be used by default.
     */
    public $id;

    /**
     * @var array HTML attributes for the field container
     */
    public $containerAttributes = [];

    /**
     * @var array HTML attributes for the input container
     */
    public $inputContainerAttributes = [];

    /**
     * @var string|null The ID of the field label
     */
    public $labelAttributes = [];

    /**
     * @var string|null The field’s orientation (`ltr` or `rtl`)
     */
    public $orientation;

    /**
     * @var bool Whether the field is translatable
     */
    public $translatable = false;

    /**
     * @inheritdoc
     */
    public function attribute(): string
    {
        return $this->attribute;
    }

    /**
     * @inheritdoc
     */
    public function mandatory(): bool
    {
        return $this->mandatory;
    }

    /**
     * @inheritdoc
     */
    public function requirable(): bool
    {
        return $this->requirable;
    }

    /**
     * @inheritdoc
     */
    protected function id(): string
    {
        return $this->id ?? parent::id();
    }

    /**
     * @inheritdoc
     */
    protected function containerAttributes(ElementInterface $element = null, bool $static = false): array
    {
        $attributes = parent::containerAttributes($element, $static);
        return ArrayHelper::merge($attributes, $this->containerAttributes);
    }

    /**
     * @inheritdoc
     */
    protected function inputContainerAttributes(ElementInterface $element = null, bool $static = false): array
    {
        return $this->inputContainerAttributes;
    }

    /**
     * @inheritdoc
     */
    protected function labelAttributes(ElementInterface $element = null, bool $static = false): array
    {
        return $this->labelAttributes;
    }

    /**
     * @inheritdoc
     */
    protected function tip(ElementInterface $element = null, bool $static = false)
    {
        return $this->tip;
    }

    /**
     * @inheritdoc
     */
    protected function warning(ElementInterface $element = null, bool $static = false)
    {
        return $this->warning;
    }

    /**
     * @inheritdoc
     */
    protected function orientation(ElementInterface $element = null, bool $static = false): string
    {
        return $this->orientation ?? parent::orientation();
    }

    /**
     * @inheritdoc
     */
    protected function translatable(ElementInterface $element = null, bool $static = false): bool
    {
        return $this->translatable;
    }
}

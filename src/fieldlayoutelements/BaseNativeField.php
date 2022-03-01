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
 * BaseNativeField is the base class for native fields that can be included in field layouts.
 *
 * Native fields can be registered using [[\craft\models\FieldLayout::EVENT_DEFINE_NATIVE_FIELDS]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
abstract class BaseNativeField extends BaseField
{
    /**
     * @var bool Whether the field *must* be present within the layout.
     */
    public bool $mandatory = false;

    /**
     * @var bool Whether the field can optionally be marked as required.
     */
    public bool $requirable = false;

    /**
     * @var string the element attribute this field is for.
     */
    public string $attribute;

    /**
     * @var string|null The input’s `id` attribute value.
     *
     * If this is not set, [[attribute()]] will be used by default.
     */
    public ?string $id = null;

    /**
     * @var array HTML attributes for the field container
     */
    public array $containerAttributes = [];

    /**
     * @var array HTML attributes for the input container
     */
    public array $inputContainerAttributes = [];

    /**
     * @var array HTML attributes for the field label
     */
    public array $labelAttributes = [];

    /**
     * @var string|null The field’s orientation (`ltr` or `rtl`)
     */
    public ?string $orientation = null;

    /**
     * @var bool Whether the field is translatable
     */
    public bool $translatable = false;

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
    protected function containerAttributes(?ElementInterface $element = null, bool $static = false): array
    {
        $attributes = parent::containerAttributes($element, $static);
        return ArrayHelper::merge($attributes, $this->containerAttributes);
    }

    /**
     * @inheritdoc
     */
    protected function inputContainerAttributes(?ElementInterface $element = null, bool $static = false): array
    {
        return $this->inputContainerAttributes;
    }

    /**
     * @inheritdoc
     */
    protected function labelAttributes(?ElementInterface $element = null, bool $static = false): array
    {
        return $this->labelAttributes;
    }

    /**
     * @inheritdoc
     */
    protected function tip(?ElementInterface $element = null, bool $static = false): ?string
    {
        return $this->tip;
    }

    /**
     * @inheritdoc
     */
    protected function warning(?ElementInterface $element = null, bool $static = false): ?string
    {
        return $this->warning;
    }

    /**
     * @inheritdoc
     */
    protected function orientation(?ElementInterface $element = null, bool $static = false): string
    {
        return $this->orientation ?? parent::orientation($element, $static);
    }

    /**
     * @inheritdoc
     */
    protected function translatable(?ElementInterface $element = null, bool $static = false): bool
    {
        return $this->translatable;
    }
}

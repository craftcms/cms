<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\Events\DefineNativeFields;
use CraftCms\Cms\Support\Arr;
use Override;

/**
 * BaseNativeField is the base class for native fields that can be included in field layouts.
 *
 * Native fields can be registered using {@see DefineNativeFields}.
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

    public function attribute(): string
    {
        return $this->attribute;
    }

    #[Override]
    public function mandatory(): bool
    {
        return $this->mandatory;
    }

    #[Override]
    public function requirable(): bool
    {
        return $this->requirable;
    }

    #[Override]
    protected function id(): string
    {
        return $this->id ?? parent::id();
    }

    #[Override]
    protected function containerAttributes(?ElementInterface $element = null, bool $static = false): array
    {
        $attributes = parent::containerAttributes($element, $static);

        return Arr::merge($attributes, $this->containerAttributes);
    }

    #[Override]
    protected function inputContainerAttributes(?ElementInterface $element = null, bool $static = false): array
    {
        return $this->inputContainerAttributes;
    }

    #[Override]
    protected function labelAttributes(?ElementInterface $element = null, bool $static = false): array
    {
        return $this->labelAttributes;
    }

    #[Override]
    protected function orientation(?ElementInterface $element = null, bool $static = false): string
    {
        return $this->orientation ?? parent::orientation($element, $static);
    }

    #[Override]
    protected function translatable(?ElementInterface $element = null, bool $static = false): bool
    {
        return $this->translatable;
    }
}

<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\ui\components;

use Craft;
use craft\base\BaseUiComponent;
use craft\helpers\ArrayHelper;
use craft\ui\attributes\AsTwigComponent;
use craft\ui\HtmlAttributes;

#[AsTwigComponent('input:select')]
class InputSelect extends BaseUiComponent
{
    /**
     * ID for the input
     *
     * @var string|null
     */
    public ?string $id = null;

    /**
     * Options for the select field.
     *
     * Options can be passed in as either a simple or associative array. If using
     * an associative array, each item can have a `value`, `disabled`, `hidden`,
     * and `data` attribute.
     *
     * @var array
     */
    public array $options = [];

    /**
     * Value of the input
     *
     * @var string|null
     */
    public ?string $value = null;

    /**
     * If the input is disabled
     *
     * @var bool
     */
    public bool $disabled = false;

    /**
     * If option groups are used
     *
     * @var bool
     */
    public bool $hasOptgroups = false;

    /**
     * Display as a toggle field
     *
     * @var bool
     */
    public bool $toggle = false;

    /**
     * Shortcut for the aria-labelledBy property of the input
     *
     * @var string|null
     */
    public ?string $labelledBy = null;

    /**
     * Shortcut for the aria-describedBy property of the input
     *
     * @var string|null
     */
    public ?string $describedBy = null;

    /**
     * @var string|null
     */
    public ?string $targetPrefix = null;

    /**
     * Attributes for the container
     *
     * @var array|HtmlAttributes
     */
    public array|HtmlAttributes $containerAttributes = [];

    /**
     * If input should be autofocused. Only applies on desktop browsers.
     *
     * @var bool
     */
    public bool $autofocus = false;

    public function mount(
        bool $autofocus = false,
        array $options = [],
        array $containerAttributes = [],
    ) {
        $this->options = $this->normalizeOptions($options);
        $this->containerAttributes = new HtmlAttributes($containerAttributes);
        $this->autofocus = $autofocus && Craft::$app->getRequest()->isMobileBrowser(true);
    }

    /**
     * Normalize the options.
     *
     * @param array $options
     * @return array
     */
    private function normalizeOptions(array $options = []): array
    {
        return array_map(function($option) {
            if (is_string($option)) {
                return [
                    'label' => $option,
                    'value' => $option,
                    'selected' => $option === $this->value,
                ];
            }

            $optionValue = $option['value'] ?? $option ?? '';
            return ArrayHelper::merge([
                'selected' => $optionValue === $this->value,
                'value' => $optionValue,
                'label' => $option['label'] ?? $option,
            ], $option);
        }, $options);
    }
}

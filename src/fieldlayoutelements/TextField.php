<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;

/**
 * TextField represents a text field that can be included in field layouts.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class TextField extends BaseNativeField
{
    /**
     * @var string The input type
     */
    public string $type = 'text';

    /**
     * @var string|bool|null The input’s `autocomplete` attribute value.
     *
     * This can be set to `true` (`"on"`), `false` ("off")`, or any other allowed `autocomplete` value.
     *
     * If this is explicitly set to `null`, the input won’t get an `autocomplete` attribute.
     */
    public string|bool|null $autocomplete = false;

    /**
     * @var string|string[]|null The input’s `class` attribute value.
     */
    public string|array|null $class = null;

    /**
     * @var int|null The input’s `size` attribute value.
     */
    public ?int $size = null;

    /**
     * @var string|null The input’s `name` attribute value.
     *
     * If this is not set, [[attribute()]] will be used by default.
     */
    public ?string $name = null;

    /**
     * @var int|null The input’s `maxlength` attribute value.
     */
    public ?int $maxlength = null;

    /**
     * @var bool Whether the input should get an `autofocus` attribute.
     */
    public bool $autofocus = false;

    /**
     * @var bool Whether the input should support autocorrect.
     */
    public bool $autocorrect = true;

    /**
     * @var bool Whether the input should support auto-capitalization.
     */
    public bool $autocapitalize = true;

    /**
     * @var bool Whether the input should get a `disabled` attribute.
     */
    public bool $disabled = false;

    /**
     * @var bool Whether the input should get a `readonly` attribute.
     */
    public bool $readonly = false;

    /**
     * @var string|null The input’s `title` attribute value.
     */
    public ?string $title = null;

    /**
     * @var string|null The input’s `placeholder` attribute value.
     */
    public ?string $placeholder = null;

    /**
     * @var int|null The input’s `step` attribute value.
     */
    public ?int $step = null;

    /**
     * @var int|null The input’s `min` attribute value.
     */
    public ?int $min = null;

    /**
     * @var int|null The input’s `max` attribute value.
     */
    public ?int $max = null;

    /**
     * @inheritdoc
     */
    public function fields(): array
    {
        $fields = parent::fields();

        // Don't include the value
        unset($fields['value']);

        return $fields;
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::$app->getView()->renderTemplate('_includes/forms/text.twig', [
            'type' => $this->type,
            'autocomplete' => $this->autocomplete,
            'class' => $this->class,
            'id' => $this->id(),
            'describedBy' => $this->describedBy($element, $static),
            'size' => $this->size,
            'name' => $this->name ?? $this->attribute(),
            'value' => $this->value($element),
            'maxlength' => $this->maxlength,
            'autofocus' => $this->autofocus,
            'autocorrect' => $this->autocorrect,
            'autocapitalize' => $this->autocapitalize,
            'disabled' => $static || $this->disabled,
            'readonly' => $this->readonly,
            'required' => !$static && $this->required,
            'title' => $this->title,
            'placeholder' => $this->placeholder,
            'step' => $this->step,
            'min' => $this->min,
            'max' => $this->max,
        ]);
    }
}

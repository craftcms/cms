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
 * StandardTextField represents a text field that can be included in field layouts.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class StandardTextField extends StandardField
{
    /**
     * @var string The input type
     */
    public $type = 'text';

    /**
     * @var string|bool|null The input’s `autocomplete` attribute value.
     *
     * This can be set to `true` (`"on"`), `false` ("off")`, or any other allowed `autocomplete` value.
     *
     * If this is explicitly set to `null`, the input won’t get an `autocomplete` attribute.
     */
    public $autocomplete = false;

    /**
     * @var string|string[]|null The input’s `class` attribute value.
     */
    public $class;

    /**
     * @var int|null The input’s `size` attribute value.
     */
    public $size;

    /**
     * @var string|null The input’s `name` attribute value.
     *
     * If this is not set, [[attribute()]] will be used by default.
     */
    public $name;

    /**
     * @var int|null The input’s `maxlength` attribute value.
     */
    public $maxlength;

    /**
     * @var bool Whether the input should get an `autofocus` attribute.
     */
    public $autofocus = false;

    /**
     * @var bool Whether the input should support autocorrect.
     */
    public $autocorrect = true;

    /**
     * @var bool Whether the input should support auto-capitalization.
     */
    public $autocapitalize = true;

    /**
     * @var bool Whether the input should get a `disabled` attribute.
     */
    public $disabled = false;

    /**
     * @var bool Whether the input should get a `readonly` attribute.
     */
    public $readonly = false;

    /**
     * @var string|null The input’s `title` attribute value.
     */
    public $title;

    /**
     * @var string|null The input’s `placeholder` attribute value.
     */
    public $placeholder;

    /**
     * @var int|null The input’s `step` attribute value.
     */
    public $step;

    /**
     * @var int|null The input’s `min` attribute value.
     */
    public $min;

    /**
     * @var int|null The input’s `max` attribute value.
     */
    public $max;

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        // Don't include the value
        unset($fields['value']);

        return $fields;
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(ElementInterface $element = null, bool $static = false)
    {
        $id = $this->id();
        return Craft::$app->getView()->renderTemplate('_includes/forms/text', [
            'type' => $this->type,
            'autocomplete' => $this->autocomplete,
            'class' => $this->class,
            'id' => $id,
            'instructionsId' => "$id-instructions",
            'size' => $this->size,
            'name' => $this->name ?? $this->attribute(),
            'value' => $this->value($element),
            'maxlength' => $this->maxlength,
            'autofocus' => $this->autofocus,
            'autocorrect' => $this->autocorrect,
            'autocapitalize' => $this->autocapitalize,
            'disabled' => $static || $this->disabled,
            'readonly' => $this->readonly,
            'title' => $this->title,
            'placeholder' => $this->placeholder,
            'step' => $this->step,
            'min' => $this->min,
            'max' => $this->max,
        ]);
    }
}

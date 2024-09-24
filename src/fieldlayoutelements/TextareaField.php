<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Html as HtmlHelper;

/**
 * TextareaField represents a textarea field that can be included in field layouts.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class TextareaField extends BaseNativeField
{
    /**
     * @var string|string[]|null The input’s `class` attribute value.
     */
    public string|array|null $class = null;

    /**
     * @var int|null The input’s `rows` attribute value.
     */
    public ?int $rows = null;

    /**
     * @var int|null The input’s `cols` attribute value.
     */
    public ?int $cols = null;

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
        return Craft::$app->getView()->renderTemplate(
            '_includes/forms/textarea.twig',
            $this->inputTemplateVariables($element, $static),
        );
    }

    /**
     * Returns the variables that should be passed to the `_includes/forms/textarea.twig` template.
     *
     * @param ElementInterface|null $element The element the form is being rendered for
     * @param bool $static Whether the form should be static (non-interactive)
     * @return array
     * @since 5.1.5
     */
    protected function inputTemplateVariables(?ElementInterface $element, bool $static): array
    {
        return [
            'class' => HtmlHelper::explodeClass($this->class),
            'id' => $this->id(),
            'describedBy' => $this->describedBy($element, $static),
            'rows' => $this->rows,
            'cols' => $this->cols,
            'name' => $this->name ?? $this->attribute(),
            'value' => $this->value($element),
            'maxlength' => $this->maxlength,
            'autofocus' => $this->autofocus,
            'disabled' => $static || $this->disabled,
            'readonly' => $this->readonly,
            'required' => !$static && $this->required,
            'title' => $this->title,
            'placeholder' => $this->placeholder,
        ];
    }

    /**
     * @inheritdoc
     */
    protected function baseInputName(): string
    {
        return $this->name ?? parent::baseInputName();
    }

    /**
     * @inheritdoc
     */
    protected function errorKey(): string
    {
        return $this->name ?? parent::errorKey();
    }
}

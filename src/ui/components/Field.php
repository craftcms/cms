<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\ui\components;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\Html;
use craft\ui\attributes\AsTwigComponent;

#[AsTwigComponent('field')]
class Field extends Input
{
    /**
     * `name` attribute of the field input
     *
     * @var string|null
     */
    public ?string $name = null;

    /**
     * ID of the component. Defaults to `'field' . mt_rand()` when null.
     *
     * @var string|null
     */
    public ?string $id = null;

    /**
     * ID attribute of the surrounding field element. Defaults to `${id}-field`
     *
     * @var string|null
     */
    public ?string $fieldId = null;

    /**
     * ID attribute for the label element. Defaults to `{$id}-label`.
     *
     * @var string|null
     */
    public ?string $labelId = null;

    /**
     * ID attribute for the errors element. Defaults to `{$id}-errors`.
     *
     * @var string|null
     */
    public ?string $errorsId = null;

    /**
     * ID attribute for the status slot. Defaults to `${id}-status`
     *
     * @var string|null
     */
    public ?string $statusId = null;

    /**
     * ID attribute for the tip element. Defaults to `${id}-tip`
     *
     * @var string|null
     */
    public ?string $tipId = null;

    /**
     * ID attribute for the warning slot. Defaults to `${id}-warning`
     *
     * @var string|null
     */
    public ?string $warningId = null;

    /**
     * ID attribute for the instructions slot. Defaults to `${id}-instructions`
     *
     * @var string|null
     */
    public ?string $instructionsId = null;

    /**
     * Mark field as required.
     *
     * @var bool
     */
    public bool $required = false;

    /**
     * Mark field as translatable.
     *
     * Note: For the icon to show, multiple sites must be present and a siteId must be determined.
     *
     * @var bool
     */
    public bool $translatable = false;

    /**
     * Non-sighted description for translation icon.
     *
     * @var string
     */
    public string $translationDescription = 'This field is translatable.';


    /**
     * Classes to add to field element
     *
     * @var string|array|null
     */
    public mixed $fieldClass = null;

    /**
     * Attribute, typically the handle, of the field.
     *
     * @var string|null
     */
    public ?string $attribute = null;

    /**
     * Render as fieldset instead of div.
     *
     * @var bool
     */
    public bool $fieldset = false;

    /**
     * Text of the label for the field.
     *
     * @var string|null
     */
    public ?string $label = null;

    /**
     * Text of the tip for the field.
     *
     * @var string|null
     */
    public ?string $tip = null;

    /**
     * Text of the warning for the field.
     *
     * @var string|null
     */
    public ?string $warning = null;

    /**
     * Text of the instructions for the field.
     *
     * @var string|null
     */
    public ?string $instructions = null;

    /**
     * Errors for the field
     *
     * @var array|null
     */
    public ?array $errors = null;

    /**
     * Class attribute to apply to labels
     *
     * @var string|null
     */
    public ?string $labelClass = null;

    /**
     * Additional HTML Attributes for the label element.
     *
     * @var array
     */
    public array $labelAttributes = [];

    /**
     * Additional HTML Attributes for the input element.
     *
     * @var array
     */
    public array $inputAttributes = [];

    /**
     * Additional HTML attributes for the containing element.
     *
     * @var array
     */
    public array $containerAttributes = [];

    /**
     * Show field attribute (handle). `attribute` must have a value to work.
     *
     * @var bool
     */
    public bool $showAttribute = false;

    /**
     * Orientation of the input (`ltr` or `rtl`). Automatically set based on site local when multisite.
     *
     * @var string|null
     */
    public ?string $orientation = null;

    /**
     * HTML of the input.
     *
     * If you'd like to completely render your own input
     *
     * @var string
     */
    public string $inputHtml = '';

    /**
     * Status of the field.
     *
     * @var array|null
     */
    public ?array $status = null;

    /**
     * Position of the instructions. Can be "before" or "after"
     *
     * @var string
     */
    public string $instructionsPosition = 'before';

    /**
     * Prefix for the label.
     *
     * @var string
     */
    public string $headingPrefix = '';

    /**
     * Suffix for the label.
     *
     * @var string
     */
    public string $headingSuffix = '';

    public function mount(
        string $id = null,
        string $name = null,
        string $labelId = null,
        string $tipId = null,
        string $warningId = null,
        string $instructionsId = null,
        string $statusId = null,
        string $fieldId = null,
        string $attribute = null,
        string $label = null,
        string $fieldLabel = null,
        bool $showAttribute = null,
        bool $translatable = null,
        int $siteId = null,
    ) {
        $currentUser = Craft::$app->getUser()->getIdentity();
        $this->attribute = $attribute ?? $id ?? null;

        $this->id = $id ?? 'field' . mt_rand();
        $this->name = $name;
        $this->fieldId = $fieldId ?? $this->id . '-field';
        $this->labelId = $labelId ?? $this->id . '-label';
        $this->tipId = $tipId ?? $this->id . '-tip';
        $this->warningId = $warningId ?? $this->id . '-warning';
        $this->instructionsId = $instructionsId ?? $this->id . '-instructions';
        $this->statusId = $statusId ?? $this->id . '-status';

        $this->showAttribute = ($attribute && $showAttribute) || ($attribute && $currentUser && $currentUser->admin && $currentUser->getPreference('showFieldHandles'));

        $this->label = $fieldLabel ?? $label ?? null;

        if ($this->label === '__blank__') {
            $this->label = null;
        }

        // Set the default input html
        $this->siteId = $siteId;
        $this->inputHtml = InputText::createAndRender($this->getInputAttributes());
        $this->translatable = Craft::$app->getIsMultiSite() && (($translatable ?? ($this->getSite() !== null)));
    }

    /**
     * Get HTML attributes for the label.
     *
     * This method will include accessibility related attributes automatically.
     *
     * @param array $attributes
     * @return array
     */
    public function getLabelAttributes(array $attributes = []): array
    {
        return ArrayHelper::merge([
            'id' => $this->labelId,
            'class' => $this->labelClass,
            'for' => !$this->fieldset ? $this->id : null,
            'aria-hidden' => $this->fieldset ? 'true' : null,
        ], $this->labelAttributes, $attributes);
    }

    /**
     * Get HTML attributes for the input.
     *
     * @param array $inputAttributes
     * @return array
     */
    public function getInputAttributes(array $inputAttributes = []): array
    {
        $descriptorIds = array_filter([
            $this->errors ? $this->errorsId : null,
            $this->status ? $this->statusId : null,
            $this->instructions ? $this->instructionsId : null,
            $this->tip ? $this->tipId : null,
            $this->warning ? $this->warningId : null,
        ]);

        return ArrayHelper::merge([
            'id' => $this->id,
            'name' => $this->name,
            'labeledBy' => $this->labelId,
            'describedBy' => $descriptorIds ? implode(' ', $descriptorIds) : null,
        ], $this->inputAttributes, $inputAttributes);
    }

    /**
     * Get HTML attributes for the containing element.
     *
     * @param array $attributes
     * @return array
     */
    public function getContainerAttributes(array $attributes = []): array
    {
        $classes = Html::explodeClass($this->fieldClass ?? []);
        $classes[] = 'field';
        return ArrayHelper::merge([
            'id' => $this->fieldId,
            'class' => implode(' ', $classes),
            'data-attribute' => $this->attribute,
        ], $this->containerAttributes, $attributes);
    }

    public function inputHtml(string $value): static
    {
        $this->inputHtml = $value;
        return $this;
    }
}

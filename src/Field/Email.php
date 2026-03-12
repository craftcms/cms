<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use craft\base\ElementInterface;
use craft\helpers\Cp;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Conditions\TextFieldConditionRule;
use CraftCms\Cms\Field\Contracts\CrossSiteCopyableFieldInterface;
use CraftCms\Cms\Field\Contracts\InlineEditableFieldInterface;
use CraftCms\Cms\Field\Contracts\MergeableFieldInterface;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Facades\Auth;
use Override;
use yii\db\Schema;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

/**
 * Email represents an Email field.
 */
class Email extends Field implements CrossSiteCopyableFieldInterface, InlineEditableFieldInterface, MergeableFieldInterface
{
    #[Override]
    public static function displayName(): string
    {
        return t('Email');
    }

    #[Override]
    public static function icon(): string
    {
        return 'at';
    }

    #[Override]
    public static function phpType(): string
    {
        return 'string|null';
    }

    #[Override]
    public static function dbType(): string
    {
        return Schema::TYPE_STRING;
    }

    /**
     * @var string|null The input’s placeholder text
     */
    public ?string $placeholder = null;

    public function __construct($config = [])
    {
        if (($config['placeholder'] ?? null) === '') {
            unset($config['placeholder']);
        }
        parent::__construct($config);
    }

    public function getSettingsHtml(): string
    {
        return $this->settingsHtml(false);
    }

    #[Override]
    public function getReadOnlySettingsHtml(): string
    {
        return $this->settingsHtml(true);
    }

    private function settingsHtml(bool $readOnly): string
    {
        return Cp::textFieldHtml([
            'label' => t('Placeholder Text'),
            'instructions' => t('The text that will be shown if the field doesn’t have a value.'),
            'id' => 'placeholder',
            'name' => 'placeholder',
            'value' => $this->placeholder,
            'errors' => $this->errors()->get('placeholder'),
            'disabled' => $readOnly,
        ]);
    }

    #[Override]
    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        return $value !== '' ? $value : null;
    }

    #[Override]
    public function serializeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        return $value !== null ? Str::idnToUtf8Email($value) : null;
    }

    #[Override]
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return template('_includes/forms/text', [
            'type' => 'email',
            'id' => $this->getInputId(),
            'describedBy' => $this->describedBy,
            'name' => $this->handle,
            'inputmode' => 'email',
            'placeholder' => t($this->placeholder, category: 'site'),
            'value' => $value,
        ]);
    }

    #[Override]
    public function prepareForElementValidation(mixed $value): mixed
    {
        if (is_string($value)) {
            return trim($value);
        }

        return $value;
    }

    #[Override]
    public function getElementRules(ElementInterface $element): array
    {
        return [
            'email:rfc',
        ];
    }

    public function getElementConditionRuleType(): string
    {
        return TextFieldConditionRule::class;
    }

    #[Override]
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        if (! $value) {
            return '';
        }
        $value = Html::encode($value);

        return "<a href=\"mailto:$value\">$value</a>";
    }

    #[Override]
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if (! $value) {
            $value = Auth::user()->email;
        }

        return $this->getPreviewHtml($value, $element ?? new Entry);
    }
}

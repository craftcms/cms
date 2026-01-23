<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use Craft;
use craft\base\ElementInterface;
use craft\fields\conditions\TextFieldConditionRule;
use craft\helpers\Cp;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Contracts\CrossSiteCopyableFieldInterface;
use CraftCms\Cms\Field\Contracts\InlineEditableFieldInterface;
use CraftCms\Cms\Field\Contracts\MergeableFieldInterface;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\PHP;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Facades\Auth;
use Override;
use yii\db\Schema;

use function CraftCms\Cms\t;

/**
 * Email represents an Email field.
 */
final class Email extends Field implements CrossSiteCopyableFieldInterface, InlineEditableFieldInterface, MergeableFieldInterface
{
    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function displayName(): string
    {
        return t('Email');
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function icon(): string
    {
        return 'at';
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function phpType(): string
    {
        return 'string|null';
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function dbType(): string
    {
        return Schema::TYPE_STRING;
    }

    /**
     * @var string|null The input’s placeholder text
     */
    public ?string $placeholder = null;

    /**
     * {@inheritdoc}
     */
    public function __construct($config = [])
    {
        if (($config['placeholder'] ?? null) === '') {
            unset($config['placeholder']);
        }
        parent::__construct($config);
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsHtml(): string
    {
        return $this->settingsHtml(false);
    }

    /**
     * {@inheritdoc}
     */
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
            'errors' => $this->getErrors('placeholder'),
            'disabled' => $readOnly,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        return $value !== '' ? $value : null;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function serializeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        return $value !== null ? Str::idnToUtf8Email($value) : null;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Craft::$app->getView()->renderTemplate('_includes/forms/text.twig', [
            'type' => 'email',
            'id' => $this->getInputId(),
            'describedBy' => $this->describedBy,
            'name' => $this->handle,
            'inputmode' => 'email',
            'placeholder' => t($this->placeholder, category: 'site'),
            'value' => $value,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getElementValidationRules(): array
    {
        return [
            ['trim'],
            ['email', 'enableIDN' => PHP::supportsIdn(), 'enableLocalIDN' => PHP::supportsIdn()],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getElementConditionRuleType(): string
    {
        return TextFieldConditionRule::class;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        if (! $value) {
            return '';
        }
        $value = Html::encode($value);

        return "<a href=\"mailto:$value\">$value</a>";
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if (! $value) {
            $value = Auth::user()->email;
        }

        return $this->getPreviewHtml($value, $element ?? new Entry);
    }
}

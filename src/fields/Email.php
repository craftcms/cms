<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\base\CrossSiteCopyableFieldInterface;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\InlineEditableFieldInterface;
use craft\base\MergeableFieldInterface;
use craft\elements\Entry;
use craft\fields\conditions\TextFieldConditionRule;
use craft\helpers\App;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use yii\db\Schema;

/**
 * Email represents an Email field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Email extends Field implements InlineEditableFieldInterface, MergeableFieldInterface, CrossSiteCopyableFieldInterface
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Email');
    }

    /**
     * @inheritdoc
     */
    public static function icon(): string
    {
        return 'at';
    }

    /**
     * @inheritdoc
     */
    public static function phpType(): string
    {
        return 'string|null';
    }

    /**
     * @inheritdoc
     */
    public static function dbType(): string
    {
        return Schema::TYPE_STRING;
    }

    /**
     * @var string|null The input’s placeholder text
     */
    public ?string $placeholder = null;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        if (($config['placeholder'] ?? null) === '') {
            unset($config['placeholder']);
        }
        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return $this->settingsHtml(false);
    }

    /**
     * @inheritdoc
     */
    public function getReadOnlySettingsHtml(): ?string
    {
        return $this->settingsHtml(true);
    }

    private function settingsHtml(bool $readOnly): string
    {
        return Cp::textFieldHtml([
            'label' => Craft::t('app', 'Placeholder Text'),
            'instructions' => Craft::t('app', 'The text that will be shown if the field doesn’t have a value.'),
            'id' => 'placeholder',
            'name' => 'placeholder',
            'value' => $this->placeholder,
            'errors' => $this->getErrors('placeholder'),
            'disabled' => $readOnly,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue(mixed $value, ElementInterface $element = null): mixed
    {
        return $value !== '' ? $value : null;
    }

    /**
     * @inheritdoc
     */
    public function serializeValue(mixed $value, ElementInterface $element = null): mixed
    {
        return $value !== null ? StringHelper::idnToUtf8Email($value) : null;
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Craft::$app->getView()->renderTemplate('_includes/forms/text.twig', [
            'type' => 'email',
            'id' => $this->getInputId(),
            'describedBy' => $this->describedBy,
            'name' => $this->handle,
            'inputmode' => 'email',
            'placeholder' => Craft::t('site', $this->placeholder),
            'value' => $value,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        return [
            ['trim'],
            ['email', 'enableIDN' => App::supportsIdn(), 'enableLocalIDN' => App::supportsIdn()],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getElementConditionRuleType(): array|string|null
    {
        return TextFieldConditionRule::class;
    }

    /**
     * @inheritdoc
     */
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        if (!$value) {
            return '';
        }
        $value = Html::encode($value);
        return "<a href=\"mailto:$value\">$value</a>";
    }

    /**
     * @inheritdoc
     */
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if (!$value) {
            $value = Craft::$app->getUser()->getIdentity()->email;
        }

        return $this->getPreviewHtml($value, $element ?? new Entry());
    }
}

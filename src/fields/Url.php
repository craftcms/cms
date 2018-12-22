<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\PreviewableFieldInterface;
use craft\helpers\Html;
use craft\validators\UrlValidator;
use yii\db\Schema;

/**
 * Url represents a URL field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Url extends Field implements PreviewableFieldInterface
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'URL');
    }

    // Properties
    // =========================================================================

    /**
     * @var string|null The input’s placeholder text
     */
    public $placeholder;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getContentColumnType(): string
    {
        return Schema::TYPE_STRING;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'textField', [
            [
                'label' => Craft::t('app', 'Placeholder Text'),
                'instructions' => Craft::t('app', 'The text that will be shown if the field doesn’t have a value.'),
                'id' => 'placeholder',
                'name' => 'placeholder',
                'value' => $this->placeholder,
                'errors' => $this->getErrors('placeholder'),
            ]
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        return Craft::$app->getView()->renderTemplate('_includes/forms/text', [
            'type' => 'url',
            'id' => $this->handle,
            'name' => $this->handle,
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
            [UrlValidator::class],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getTableAttributeHtml($value, ElementInterface $element): string
    {
        if (!$value) {
            return '';
        }
        $value = Html::encode($value);
        return "<a href=\"{$value}\" target=\"_blank\">{$value}</a>";
    }
}

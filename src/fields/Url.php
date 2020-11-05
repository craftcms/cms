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
 * @since 3.0.0
 */
class Url extends Field implements PreviewableFieldInterface
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'URL');
    }

    /**
     * @inheritdoc
     */
    public static function valueType(): string
    {
        return 'string|null';
    }

    /**
     * @var string|null The input’s placeholder text
     */
    public $placeholder;

    /**
     * @var int The maximum length (in bytes) the field can hold
     */
    public $maxLength = 255;

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['maxLength'], 'required'];
        $rules[] = [['maxLength'], 'number', 'integerOnly' => true, 'min' => 10];
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getContentColumnType(): string
    {
        return Schema::TYPE_STRING . "({$this->maxLength})";
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
            ]) .
            Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'textField', [
                [
                    'label' => Craft::t('app', 'Max Length'),
                    'instructions' => Craft::t('app', 'The maximum length (in bytes) the field can hold.'),
                    'id' => 'maxLength',
                    'name' => 'maxLength',
                    'type' => 'number',
                    'min' => '10',
                    'step' => '10',
                    'value' => $this->maxLength,
                    'errors' => $this->getErrors('maxLength'),
                ]
            ]);
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml($value, ElementInterface $element = null): string
    {
        return Craft::$app->getView()->renderTemplate('_includes/forms/text', [
            'type' => 'url',
            'id' => $this->handle,
            'name' => $this->handle,
            'inputmode' => 'url',
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

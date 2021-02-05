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
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\validators\ArrayValidator;
use craft\validators\UrlValidator;
use yii\base\InvalidConfigException;
use yii\base\InvalidValueException;
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
     * @since 3.6.0
     */
    const TYPE_URL = 'url';
    /**
     * @since 3.6.0
     */
    const TYPE_TEL = 'tel';
    /**
     * @since 3.6.0
     */
    const TYPE_EMAIL = 'email';

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
     * @var string[] Allowed URL types
     * @since 3.6.0
     */
    public $types = [
        self::TYPE_URL,
    ];

    /**
     * @var string|null The input’s placeholder text
     * @deprecated in 3.6.0
     */
    public $placeholder;

    /**
     * @var int The maximum length (in bytes) the field can hold
     */
    public $maxLength = 255;

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();
        unset($fields['placeholder']);
        return $fields;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['types'], ArrayValidator::class];
        $rules[] = [['types', 'maxLength'], 'required'];
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
        return
            Cp::checkboxSelectFieldHtml([
                'label' => Craft::t('app', 'Allowed URL Types'),
                'id' => 'types',
                'name' => 'types',
                'options' => [
                    ['label' => Craft::t('app', 'Web page'), 'value' => self::TYPE_URL],
                    ['label' => Craft::t('app', 'Telephone'), 'value' => self::TYPE_TEL],
                    ['label' => Craft::t('app', 'Email'), 'value' => self::TYPE_EMAIL],
                ],
                'values' => $this->types,
                'required' => true,
            ]) .
            Cp::textFieldHtml([
                'label' => Craft::t('app', 'Max Length'),
                'instructions' => Craft::t('app', 'The maximum length (in bytes) the field can hold.'),
                'id' => 'maxLength',
                'name' => 'maxLength',
                'type' => 'number',
                'min' => '10',
                'step' => '10',
                'value' => $this->maxLength,
                'errors' => $this->getErrors('maxLength'),
            ]);
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        if (is_array($value) && isset($value['value'])) {
            $type = $value['type'] ?? self::TYPE_URL;
            $value = trim($value['value']);

            if ($value) {
                switch ($type) {
                    case self::TYPE_TEL:
                        $value = str_replace(' ', '-', $value);
                        $value = StringHelper::ensureLeft($value, 'tel:');
                        break;
                    case self::TYPE_EMAIL:
                        $value = StringHelper::ensureLeft($value, 'mailto:');
                        break;
                    case self::TYPE_URL:
                        if (!UrlHelper::isFullUrl($value)) {
                            $value = StringHelper::ensureLeft($value, 'http://');
                        }
                        break;
                    default:
                        throw new InvalidValueException("Invalid URL type: $type");
                }
            }
        }

        if ($value === '') {
            return null;
        }

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function useFieldset(): bool
    {
        return count($this->types) > 1;
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml($value, ElementInterface $element = null): string
    {
        if (is_string($value)) {
            $valueType = $this->_urlType($value);
        } else {
            $valueType = self::TYPE_URL;
        }

        if (!in_array($valueType, $this->types, true)) {
            $valueType = reset($this->types);
        }

        $id = Html::id($this->handle);
        $typeOptions = [];

        foreach ($this->types as $type) {
            switch ($type) {
                case self::TYPE_URL:
                    $label = Craft::t('app', 'Web page');
                    $prefix = null;
                    break;
                case self::TYPE_TEL:
                    $label = Craft::t('app', 'Telephone');
                    $prefix = 'tel:';
                    break;
                case self::TYPE_EMAIL:
                    $label = Craft::t('app', 'Email');
                    $prefix = 'mailto:';
                    break;
                default:
                    throw new InvalidConfigException("Invalid URL type: $type");
            }

            $typeOptions[] = ['label' => $label, 'value' => $type];

            if (is_string($value) && $type === $valueType && $prefix) {
                $value = StringHelper::removeLeft($value, $prefix);
            }
        }

        $input = Craft::$app->getView()->renderTemplate('_includes/forms/text', [
            'id' => $id,
            'instructionsId' => "$id-instructions",
            'class' => ['flex-grow', 'fullwidth'],
            'type' => $valueType,
            'name' => "$this->handle[value]",
            'inputmode' => $valueType,
            'value' => $value,
            'inputAttributes' => [
                'aria' => [
                    'label' => Craft::t('site', $this->name),
                ],
            ],
        ]);

        if (count($this->types) === 1) {
            return $input;
        }

        $view = Craft::$app->getView();
        $namespacedId = $view->namespaceInputId($id);
        $js = <<<JS
$('#$namespacedId-type').on('change', e => { 
  const type = $('#$namespacedId-type').val();
  $('#$namespacedId')
    .attr('type', type)
    .attr('inputmode', type);
});
JS;
        $view->registerJs($js);

        return Html::tag(
            'div',
            Cp::selectHtml([
                'id' => "$id-type",
                'name' => "$this->handle[type]",
                'options' => $typeOptions,
                'value' => $valueType,
                'inputAttributes' => [
                    'aria' => [
                        'label' => Craft::t('app', 'URL type'),
                    ],
                ],
            ]) .
            $input,
            [
                'class' => ['flex', 'flex-nowrap'],
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        $patterns = [];

        foreach ($this->types as $type) {
            switch ($type) {
                case self::TYPE_URL:
                    $patterns[] = UrlValidator::URL_PATTERN;
                    break;
                case self::TYPE_TEL:
                    // * and # characters are not allowed by iOS
                    // see  https://developer.apple.com/library/archive/featuredarticles/iPhoneURLScheme_Reference/PhoneLinks/PhoneLinks.html
                    $patterns[] = '^tel:[\d\+\(\)\-,;]+$';
                    break;
                case self::TYPE_EMAIL:
                    // Regex taken from EmailValidator::$pattern
                    $patterns[] = '^mailto:[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$';
                    break;
            }
        }

        return [
            ['trim'],
            [
                UrlValidator::class,
                'pattern' => '/' . implode('|', $patterns) . '/i',
            ],
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

    /**
     * Returns what type of URL a given value is.
     *
     * @param string $value
     * @return string
     */
    private function _urlType(string $value): string
    {
        if (strpos($value, 'tel:') === 0) {
            return self::TYPE_TEL;
        }

        if (strpos($value, 'mailto:') === 0) {
            return self::TYPE_EMAIL;
        }

        return self::TYPE_URL;
    }
}

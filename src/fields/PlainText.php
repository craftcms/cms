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
use craft\helpers\Db;
use LitEmoji\LitEmoji;
use yii\db\Schema;

/**
 * PlainText represents a Plain Text field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class PlainText extends Field implements PreviewableFieldInterface
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Plain Text');
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
     * @var bool Whether the input should use monospace font
     */
    public $code = false;

    /**
     * @var bool Whether the input should allow line breaks
     */
    public $multiline = false;

    /**
     * @var int The minimum number of rows the input should have, if multi-line
     */
    public $initialRows = 4;

    /**
     * @var int|null The maximum number of characters allowed in the field
     */
    public $charLimit;

    /**
     * @var int|null The maximum number of bytes allowed in the field
     * @since 3.4.0
     */
    public $byteLimit;

    /**
     * @var string|null The type of database column the field should have in the content table
     */
    public $columnType;

    /**
     * @inheritdoc
     */
    public function __construct(array $config = [])
    {
        if (isset($config['limitUnit'], $config['fieldLimit'])) {
            if ($config['limitUnit'] === 'chars') {
                $config['charLimit'] = (int)$config['fieldLimit'] ?: null;
            } else {
                $config['byteLimit'] = (int)$config['fieldLimit'] ?: null;
            }
            unset($config['limitUnit'], $config['fieldLimit']);
        }

        if (isset($config['charLimit']) && empty($config['charLimit'])) {
            unset($config['charLimit']);
        }

        if (isset($config['byteLimit']) && empty($config['byteLimit'])) {
            unset($config['byteLimit']);
        }

        if (isset($config['columnType']) && $config['columnType'] === 'auto') {
            unset($config['columnType']);
        }

        // This existed at one point way back in the day.
        unset($config['maxLengthUnit']);

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['initialRows', 'charLimit', 'byteLimit'], 'integer', 'min' => 1];
        $rules[] = [['charLimit', 'byteLimit'], 'validateFieldLimit'];
        return $rules;
    }

    /**
     * Validates that the Character Limit isn't set to something higher than the Column Type will hold.
     *
     * @param string $attribute
     */
    public function validateFieldLimit(string $attribute)
    {
        if ($bytes = $this->$attribute) {
            if ($attribute === 'charLimit') {
                $bytes *= 4;
            }
            $columnTypeMax = Db::getTextualColumnStorageCapacity($this->getContentColumnType());
            if ($columnTypeMax && $columnTypeMax < $bytes) {
                $this->addError($attribute, Craft::t('app', 'Field Limit is too big for your chosen Column Type.'));
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('_components/fieldtypes/PlainText/settings',
            [
                'field' => $this
            ]);
    }

    /**
     * @inheritdoc
     */
    public function getContentColumnType(): string
    {
        if ($this->columnType) {
            return $this->columnType;
        }

        if ($this->byteLimit) {
            $bytes = $this->byteLimit;
        } else if ($this->charLimit) {
            $bytes = $this->charLimit * 4;
        } else {
            return Schema::TYPE_TEXT;
        }

        return Schema::TYPE_STRING . "({$bytes})";
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        if ($value !== null) {
            $value = LitEmoji::shortcodeToUnicode($value);
            $value = trim(preg_replace('/\R/u', "\n", $value));
        }

        return $value !== '' ? $value : null;
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        return Craft::$app->getView()->renderTemplate('_components/fieldtypes/PlainText/input',
            [
                'name' => $this->handle,
                'value' => $value,
                'field' => $this,
            ]);
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        return [
            [
                'string',
                'max' => $this->byteLimit ?? $this->charLimit ?? null,
                'encoding' => $this->byteLimit ? '8bit' : 'UTF-8',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function serializeValue($value, ElementInterface $element = null)
    {
        if ($value !== null) {
            $value = LitEmoji::unicodeToShortcode($value);
        }
        return $value;
    }

    /**
     * @inheritdoc
     */
    public function getSearchKeywords($value, ElementInterface $element): string
    {
        $value = (string)$value;
        $value = LitEmoji::unicodeToShortcode($value);
        return $value;
    }
}

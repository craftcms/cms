<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\base;

use Craft;
use craft\app\elements\db\ElementQuery;
use craft\app\elements\db\ElementQueryInterface;
use craft\app\helpers\Db;
use craft\app\helpers\Html;
use craft\app\helpers\StringHelper;
use craft\app\records\Field as FieldRecord;
use craft\app\validators\HandleValidator;
use craft\app\validators\UniqueValidator;
use Exception;
use yii\base\ErrorHandler;
use yii\db\Schema;

/**
 * Field is the base class for classes representing fields in terms of objects.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
abstract class Field extends SavableComponent implements FieldInterface
{
    // Traits
    // =========================================================================

    use FieldTrait;

    // Constants
    // =========================================================================

    const TRANSLATION_METHOD_NONE = 'none';
    const TRANSLATION_METHOD_LANGUAGE = 'language';
    const TRANSLATION_METHOD_SITE = 'site';
    const TRANSLATION_METHOD_CUSTOM = 'custom';

    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function hasContentColumn()
    {
        return true;
    }

    // Properties
    // =========================================================================

    /**
     * @var boolean Whether the field is fresh.
     * @see isFresh()
     * @see setIsFresh()
     */
    private $_isFresh;

    // Public Methods
    // =========================================================================

    /**
     * Use the translated field name as the string representation.
     *
     * @return string
     */
    /** @noinspection PhpInconsistentReturnPointsInspection */
    public function __toString()
    {
        try {
            return Craft::t('site', $this->name);
        } catch (Exception $e) {
            ErrorHandler::convertExceptionToError($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        // TODO: MySQL specific
        $maxHandleLength = 64 - strlen(Craft::$app->getContent()->fieldColumnPrefix);

        $rules = [
            [['name'], 'string', 'max' => 255],
            [['type'], 'string', 'max' => 150],
            [['handle'], 'string', 'max' => $maxHandleLength],
            [['name', 'handle', 'context', 'type', 'translationMethod'], 'required'],
            [['groupId'], 'number', 'integerOnly' => true],
            [
                ['translationMethod'],
                'in',
                'range' => [
                    self::TRANSLATION_METHOD_NONE,
                    self::TRANSLATION_METHOD_LANGUAGE,
                    self::TRANSLATION_METHOD_SITE,
                    self::TRANSLATION_METHOD_CUSTOM
                ]
            ],
            [
                ['handle'],
                HandleValidator::class,
                'reservedWords' => [
                    'archived',
                    'attributeLabel',
                    'children',
                    'contentTable',
                    'dateCreated',
                    'dateUpdated',
                    'enabled',
                    'id',
                    'level',
                    'lft',
                    'link',
                    'enabledForSite',
                    'name', // global set-specific
                    'next',
                    'next',
                    'owner',
                    'parents',
                    'postDate', // entry-specific
                    'prev',
                    'ref',
                    'rgt',
                    'root',
                    'searchScore',
                    'siblings',
                    'site',
                    'slug',
                    'sortOrder',
                    'status',
                    'title',
                    'uid',
                    'uri',
                    'url',
                    'username', // user-specific
                ]
            ],
            [
                ['handle'],
                UniqueValidator::class,
                'targetClass' => FieldRecord::class,
                'targetAttribute' => ['handle', 'context']
            ],
        ];

        // Only validate the ID if it's not a new field
        if ($this->id !== null && strncmp($this->id, 'new', 3) !== 0) {
            $rules[] = [['id'], 'number', 'integerOnly' => true];
        }

        if ($this->translationMethod == self::TRANSLATION_METHOD_CUSTOM) {
            $rules[] = [['translationKeyFormat'], 'required'];
        }

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getContentColumnType()
    {
        return Schema::TYPE_STRING;
    }

    /**
     * @inheritdoc
     */
    public function getTranslationKey($element)
    {
        /** @var Element $element */
        switch ($this->translationMethod) {
            case self::TRANSLATION_METHOD_NONE:
                return '1';
            case self::TRANSLATION_METHOD_LANGUAGE:
                return $element->getSite()->language;
            case self::TRANSLATION_METHOD_SITE:
                return (string)$element->siteId;
            default:
                return Craft::$app->getView()->renderObjectTemplate($this->translationKeyFormat, $element);
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeElementSave(ElementInterface $element)
    {
    }

    /**
     * @inheritdoc
     */
    public function afterElementSave(ElementInterface $element)
    {
    }

    /**
     * @inheritdoc
     */
    public function prepareValue($value, $element)
    {
        return $value;
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, $element)
    {
        return Html::encodeParams('<textarea name="{name}">{value}</textarea>',
            [
                'name' => $this->handle,
                'value' => $value
            ]);
    }

    /**
     * @inheritdoc
     */
    public function getStaticHtml($value, $element)
    {
        // Just return the input HTML with disabled inputs by default
        Craft::$app->getView()->startJsBuffer();
        $inputHtml = $this->getInputHtml($value, $element);
        $inputHtml = preg_replace('/<(?:input|textarea|select)\s[^>]*/i', '$0 disabled', $inputHtml);
        Craft::$app->getView()->clearJsBuffer();

        return $inputHtml;
    }

    /**
     * @inheritdoc
     */
    public function validateValue($value, $element)
    {
        if ($this->required && $this->isValueEmpty($value, $element)) {
            return [Craft::t('yii', '{attribute} cannot be blank.')];
        }

        return [];
    }

    /**
     * @inheritdoc
     */
    public function getSearchKeywords($value, $element)
    {
        return StringHelper::toString($value, ' ');
    }

    /**
     * Returns the HTML that should be shown for this field in Table View.
     *
     * @param mixed            $value   The field’s value
     * @param ElementInterface $element The element the field is associated with
     *
     * @return string|null The HTML that should be shown for this field in Table View
     */
    public function getTableAttributeHtml($value, $element)
    {
        $value = (string)$value;

        return StringHelper::stripHtml($value);
    }

    /**
     * @inheritdoc
     */
    public function prepareValueForDb($value, $element)
    {
        return Db::prepareValueForDb($value);
    }

    /**
     * @inheritdoc
     */
    public function modifyElementsQuery(ElementQueryInterface $query, $value)
    {
        if ($value !== null) {
            // If the field type doesn't have a content column, it *must* override this method
            // if it wants to support a custom query criteria attribute
            if (!static::hasContentColumn()) {
                return false;
            }

            $handle = $this->handle;
            /** @var ElementQuery $query */
            $query->subQuery->andWhere(Db::parseParam('content.'.Craft::$app->getContent()->fieldColumnPrefix.$handle, $value, $query->subQuery->params));
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function setIsFresh($isFresh)
    {
        $this->_isFresh = $isFresh;
    }

    /**
     * @inheritdoc
     */
    public function getGroup()
    {
        return Craft::$app->getFields()->getGroupById($this->groupId);
    }

    // Protected Methods
    // =========================================================================

    /**
     * Returns whether the given value should be considered "empty" for required-field validation purposes.
     *
     * @param mixed            $value   The field’s value
     * @param ElementInterface $element The element the field is associated with, if there is one
     *
     * @return boolean Whether the value should be considered "empty"
     */
    protected function isValueEmpty($value, $element)
    {
        return empty($value);
    }

    /**
     * Returns the location in POST that this field's content was pulled from.
     *
     * @param ElementInterface $element The element this field is associated with
     *
     * @return string|null
     */
    protected function getContentPostLocation($element)
    {
        if ($element) {
            $elementContentPostLocation = $element->getContentPostLocation();

            if ($elementContentPostLocation) {
                return $elementContentPostLocation.'.'.$this->handle;
            }
        }

        return null;
    }

    /**
     * Returns this field’s value on a given element.
     *
     * @param ElementInterface $element The element
     *
     * @return mixed The field’s value
     */
    protected function getElementValue(ElementInterface $element)
    {
        return $element->getFieldValue($this->handle);
    }

    /**
     * Updates this field’s value on a given element.
     *
     * @param ElementInterface $element The element
     * @param mixed            $value   The field’s new value
     */
    protected function setElementValue(ElementInterface $element, $value)
    {
        $element->setFieldValue($this->handle, $value);
    }

    /**
     * Returns whether this is the first time the element's content has been edited.
     *
     * @param ElementInterface|null $element
     *
     * @return boolean
     */
    protected function isFresh($element)
    {
        if (!isset($this->_isFresh)) {
            if ($element) {
                $this->_isFresh = $element->getHasFreshContent();
            } else {
                $this->_isFresh = true;
            }
        }

        return $this->_isFresh;
    }
}

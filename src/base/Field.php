<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\base;

use Craft;
use craft\app\elements\db\ElementQuery;
use craft\app\elements\db\ElementQueryInterface;
use craft\app\elements\MatrixBlock;
use craft\app\events\Event;
use craft\app\helpers\Db;
use craft\app\helpers\Html;
use craft\app\helpers\StringHelper;
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

    /**
     * @event Event The event that is triggered before the field is saved
     *
     * You may set [[Event::isValid]] to `false` to prevent the field from getting saved.
     */
    const EVENT_BEFORE_SAVE = 'beforeSave';

    /**
     * @event Event The event that is triggered after the field is saved
     */
    const EVENT_AFTER_SAVE = 'afterSave';

    /**
     * @event Event The event that is triggered before the field is deleted
     *
     * You may set [[Event::isValid]] to `false` to prevent the field from getting deleted.
     */
    const EVENT_BEFORE_DELETE = 'beforeDelete';

    /**
     * @event Event The event that is triggered after the field is deleted
     */
    const EVENT_AFTER_DELETE = 'afterDelete';

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
        $rules = [
            [['name', 'handle'], 'required'],
            [
                ['groupId'],
                'number',
                'min' => -2147483648,
                'max' => 2147483647,
                'integerOnly' => true
            ],
        ];

        // Only validate the ID if it's not a new field
        if ($this->id !== null && strncmp($this->id, 'new', 3) !== 0) {
            $rules[] = [
                ['id'],
                'number',
                'min' => -2147483648,
                'max' => 2147483647,
                'integerOnly' => true
            ];
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
    public function beforeSave()
    {
        // Trigger a 'beforeSave' event
        $event = new Event();
        $this->trigger(self::EVENT_BEFORE_SAVE, $event);

        return $event->isValid;
    }

    /**
     * @inheritdoc
     */
    public function afterSave()
    {
        // Trigger an 'afterSave' event
        $this->trigger(self::EVENT_AFTER_SAVE, new Event());
    }

    /**
     * @inheritdoc
     */
    public function beforeDelete()
    {
        // Trigger a 'beforeDelete' event
        $event = new Event();
        $this->trigger(self::EVENT_BEFORE_DELETE, $event);

        return $event->isValid;
    }

    /**
     * @inheritdoc
     */
    public function afterDelete()
    {
        // Trigger an 'afterDelete' event
        $this->trigger(self::EVENT_AFTER_DELETE, new Event());
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
        } else {
            return [];
        }
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
            if (self::hasContentColumn()) {
                $handle = $this->handle;
                /** @var ElementQuery $query */
                $query->subQuery->andWhere(Db::parseParam('content.'.Craft::$app->getContent()->fieldColumnPrefix.$handle, $value, $query->subQuery->params));
            }

            return false;
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
            // If this is for a Matrix block, we're more interested in its owner
            if (isset($element) && $element instanceof MatrixBlock) {
                $element = $element->getOwner();
            }

            $this->_isFresh = (!$element || (!$element->contentId && !$element->hasErrors()));
        }

        return $this->_isFresh;
    }
}

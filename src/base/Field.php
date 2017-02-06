<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\base;

use Craft;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\events\FieldElementEvent;
use craft\helpers\Db;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use craft\records\Field as FieldRecord;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;
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

    // Events
    // -------------------------------------------------------------------------

    /**
     * @event FieldElementEvent The event that is triggered before the element is saved
     *
     * You may set [[FieldElementEvent::isValid]] to `false` to prevent the element from getting saved.
     */
    const EVENT_BEFORE_ELEMENT_SAVE = 'beforeElementSave';

    /**
     * @event FieldElementEvent The event that is triggered after the element is saved
     */
    const EVENT_AFTER_ELEMENT_SAVE = 'afterElementSave';

    /**
     * @event FieldElementEvent The event that is triggered before the element is deleted
     *
     * You may set [[FieldElementEvent::isValid]] to `false` to prevent the element from getting deleted.
     */
    const EVENT_BEFORE_ELEMENT_DELETE = 'beforeElementDelete';

    /**
     * @event FieldElementEvent The event that is triggered after the element is deleted
     */
    const EVENT_AFTER_ELEMENT_DELETE = 'afterElementDelete';

    // Translation methods
    // -------------------------------------------------------------------------

    const TRANSLATION_METHOD_NONE = 'none';
    const TRANSLATION_METHOD_LANGUAGE = 'language';
    const TRANSLATION_METHOD_SITE = 'site';
    const TRANSLATION_METHOD_CUSTOM = 'custom';

    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function hasContentColumn(): bool
    {
        return true;
    }

    // Properties
    // =========================================================================

    /**
     * @var bool|null Whether the field is fresh.
     * @see isFresh()
     * @see setIsFresh()
     */
    private $_isFresh;

    // Public Methods
    // =========================================================================

    /** @noinspection PhpInconsistentReturnPointsInspection */
    /**
     * Use the translated field name as the string representation.
     *
     * @return string
     */
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
        // Make sure the column name is under the databases maximum column length allowed.
        $maxHandleLength = Craft::$app->getDb()->getSchema()->maxObjectNameLength - strlen(Craft::$app->getContent()->fieldColumnPrefix);

        $rules = [
            [['name'], 'string', 'max' => 255],
            [['handle'], 'string', 'max' => $maxHandleLength],
            [['name', 'handle', 'translationMethod'], 'required'],
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
        if ($this->id !== null && strpos($this->id, 'new') !== 0) {
            $rules[] = [['id'], 'number', 'integerOnly' => true];
        }

        if ($this->translationMethod === self::TRANSLATION_METHOD_CUSTOM) {
            $rules[] = [['translationKeyFormat'], 'required'];
        }

        return $rules;
    }

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
    public function getTranslationKey(ElementInterface $element): string
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
    public function normalizeValue($value, ElementInterface $element = null)
    {
        return $value;
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
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
    public function getStaticHtml($value, ElementInterface $element): string
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
    public function getElementValidationRules(): array
    {
        $rules = [];

        if ($this->required) {
            $rules[] = 'required';
        }

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getSearchKeywords($value, ElementInterface $element): string
    {
        return StringHelper::toString($value, ' ');
    }

    /**
     * Returns the HTML that should be shown for this field in Table View.
     *
     * @param mixed            $value   The field’s value
     * @param ElementInterface $element The element the field is associated with
     *
     * @return string The HTML that should be shown for this field in Table View
     */
    public function getTableAttributeHtml($value, ElementInterface $element): string
    {
        $value = (string)$value;

        return StringHelper::stripHtml($value);
    }

    /**
     * @inheritdoc
     */
    public function serializeValue($value, ElementInterface $element = null)
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
            $query->subQuery->andWhere(Db::parseParam('content.'.Craft::$app->getContent()->fieldColumnPrefix.$handle, $value));
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function setIsFresh(bool $isFresh = null)
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

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function beforeElementSave(ElementInterface $element, bool $isNew): bool
    {
        // Trigger a 'beforeElementSave' event
        $event = new FieldElementEvent([
            'element' => $element,
            'isNew' => $isNew,
        ]);
        $this->trigger(self::EVENT_BEFORE_ELEMENT_SAVE, $event);

        return $event->isValid;
    }

    /**
     * @inheritdoc
     */
    public function afterElementSave(ElementInterface $element, bool $isNew)
    {
        // Trigger an 'afterElementSave' event
        $this->trigger(self::EVENT_AFTER_ELEMENT_SAVE, new FieldElementEvent([
            'element' => $element,
            'isNew' => $isNew,
        ]));
    }

    /**
     * @inheritdoc
     */
    public function beforeElementDelete(ElementInterface $element): bool
    {
        // Trigger a 'beforeElementDelete' event
        $event = new FieldElementEvent([
            'element' => $element,
        ]);
        $this->trigger(self::EVENT_BEFORE_ELEMENT_DELETE, $event);

        return $event->isValid;
    }

    /**
     * @inheritdoc
     */
    public function afterElementDelete(ElementInterface $element)
    {
        // Trigger an 'afterElementDelete' event
        $this->trigger(self::EVENT_AFTER_ELEMENT_DELETE, new FieldElementEvent([
            'element' => $element,
        ]));
    }

    // Protected Methods
    // =========================================================================

    /**
     * Returns whether the given value should be considered "empty" for required-field validation purposes.
     *
     * @param mixed            $value   The field’s value
     * @param ElementInterface $element The element the field is associated with, if there is one
     *
     * @return bool Whether the value should be considered "empty"
     */
    protected function isValueEmpty($value, ElementInterface $element): bool
    {
        return empty($value);
    }

    /**
     * Returns the field’s param name on the request.
     *
     * @param ElementInterface $element The element this field is associated with
     *
     * @return string|null The field’s param name on the request
     */
    protected function requestParamName(ElementInterface $element)
    {
        if (!$element) {
            return null;
        }

        $namespace = $element->getFieldParamNamespace();

        if (!$namespace === null) {
            return null;
        }

        return ($namespace ? $namespace.'.' : '').$this->handle;
    }

    /**
     * Returns whether this is the first time the element's content has been edited.
     *
     * @param ElementInterface|null $element
     *
     * @return bool
     */
    protected function isFresh(ElementInterface $element = null): bool
    {
        if ($this->_isFresh !== null) {
            return $this->_isFresh;
        }

        if ($element) {
            return $this->_isFresh = $element->getHasFreshContent();
        }

        return true;
    }
}

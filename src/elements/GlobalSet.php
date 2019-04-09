<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements;

use Craft;
use craft\base\Element;
use craft\behaviors\FieldLayoutBehavior;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\GlobalSetQuery;
use craft\helpers\UrlHelper;
use craft\records\GlobalSet as GlobalSetRecord;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;

/**
 * GlobalSet represents a global set element.
 *
 * @mixin FieldLayoutBehavior
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class GlobalSet extends Element
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Global Set');
    }

    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('app', 'Global Sets');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle()
    {
        return 'globalset';
    }

    /**
     * @inheritdoc
     */
    public static function hasContent(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function isLocalized(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     * @return GlobalSetQuery The newly created [[GlobalSetQuery]] instance.
     */
    public static function find(): ElementQueryInterface
    {
        return new GlobalSetQuery(static::class);
    }

    // Properties
    // =========================================================================

    /**
     * @var string|null Name
     */
    public $name;

    /**
     * @var string|null Handle
     */
    public $handle;

    // Public Methods
    // =========================================================================

    /**
     * Use the global set's name as its string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->name ?: static::class;
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['fieldLayout'] = [
            'class' => FieldLayoutBehavior::class,
            'elementType' => __CLASS__
        ];
        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['fieldLayoutId'], 'number', 'integerOnly' => true];
        $rules[] = [['name', 'handle'], 'string', 'max' => 255];
        $rules[] = [['name', 'handle'], 'required'];

        $rules[] = [
            ['name', 'handle'],
            UniqueValidator::class,
            'targetClass' => GlobalSetRecord::class
        ];

        $rules[] = [
            ['handle'],
            HandleValidator::class,
            'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']
        ];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getFieldLayout()
    {
        /** @var FieldLayoutBehavior $behavior */
        $behavior = $this->getBehavior('fieldLayout');
        return $behavior->getFieldLayout();
    }

    /**
     * @inheritdoc
     */
    public function getCpEditUrl()
    {
        if (Craft::$app->getIsMultiSite()) {
            return UrlHelper::cpUrl('globals/' . $this->getSite()->handle . '/' . $this->handle);
        }

        return UrlHelper::cpUrl('globals/' . $this->handle);
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function beforeDelete(): bool
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        if (($fieldLayout = $this->getFieldLayout()) !== null) {
            Craft::$app->getFields()->deleteLayout($fieldLayout);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterRestore()
    {
        // Restore the field layout too
        if (!Craft::$app->getFields()->restoreLayoutById($this->fieldLayoutId)) {
            Craft::warning("Global set {$this->id} restored, but its field layout ({$this->fieldLayoutId}) was not.");
        }

        parent::afterRestore();
    }
}

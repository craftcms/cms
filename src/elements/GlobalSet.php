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
use craft\db\Table;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\GlobalSetQuery;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\records\GlobalSet as GlobalSetRecord;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;

/**
 * GlobalSet represents a global set element.
 *
 * @mixin FieldLayoutBehavior
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class GlobalSet extends Element
{
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
    public static function lowerDisplayName(): string
    {
        return Craft::t('app', 'global set');
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
    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('app', 'global sets');
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
     * @return string|null
     */
    public function getRef()
    {
        return $this->handle;
    }

    /**
     * @inheritdoc
     */
    public function getIsEditable(): bool
    {
        return Craft::$app->getUser()->checkPermission("editGlobalSet:$this->uid");
    }

    /**
     * @inheritdoc
     * @return GlobalSetQuery The newly created [[GlobalSetQuery]] instance.
     */
    public static function find(): ElementQueryInterface
    {
        return new GlobalSetQuery(static::class);
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public static function gqlTypeNameByContext($context): string
    {
        /** @var self $context */
        return $context->handle . '_GlobalSet';
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public static function gqlScopesByContext($context): array
    {
        /** @var self $context */
        return ['globalsets.' . $context->uid];
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public static function gqlMutationNameByContext($context): string
    {
        /** @var self $context */
        return 'save_' . $context->handle . '_GlobalSet';
    }

    /**
     * @var string|null Name
     */
    public $name;

    /**
     * @var string|null Handle
     */
    public $handle;

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
            'elementType' => __CLASS__,
        ];
        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
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

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public function getGqlTypeName(): string
    {
        return static::gqlTypeNameByContext($this);
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
        if (
            $this->fieldLayoutId &&
            !Craft::$app->getFields()->restoreLayoutById($this->fieldLayoutId)
        ) {
            Craft::warning("Global set {$this->id} restored, but its field layout ({$this->fieldLayoutId}) was not.");
        }

        parent::afterRestore();
    }

    /**
     * Returns the field layout config for this global set.
     *
     * @return array
     * @since 3.5.0
     */
    public function getConfig(): array
    {
        $config = [
            'name' => $this->name,
            'handle' => $this->handle,
        ];

        $fieldLayout = $this->getFieldLayout();

        if ($fieldLayoutConfig = $fieldLayout->getConfig()) {
            if (!$fieldLayout->uid) {
                $fieldLayout->uid = $fieldLayout->id ? Db::uidById(Table::FIELDLAYOUTS, $fieldLayout->id) : StringHelper::UUID();
            }
            $config['fieldLayouts'] = [
                $fieldLayout->uid => $fieldLayoutConfig,
            ];
        }

        return $config;
    }
}

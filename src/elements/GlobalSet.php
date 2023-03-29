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
use craft\elements\db\GlobalSetQuery;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\records\GlobalSet as GlobalSetRecord;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;
use yii\base\InvalidConfigException;

/**
 * GlobalSet represents a global set element.
 *
 * @mixin FieldLayoutBehavior
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class GlobalSet extends Element
{
    // Validation scenarios
    // -------------------------------------------------------------------------

    /**
     * @since 4.4.6
     */
    public const SCENARIO_SAVE_SET = 'saveSet';

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Global set');
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
        return Craft::t('app', 'Global sets');
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
    public static function refHandle(): ?string
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
    public function getRef(): ?string
    {
        return $this->handle;
    }

    /**
     * @inheritdoc
     */
    public function canView(User $user): bool
    {
        return $user->can("editGlobalSet:$this->uid");
    }

    /**
     * @inheritdoc
     */
    public function canSave(User $user): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function canDuplicate(User $user): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function canDelete(User $user): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     * @return GlobalSetQuery The newly created [[GlobalSetQuery]] instance.
     */
    public static function find(): GlobalSetQuery
    {
        return new GlobalSetQuery(static::class);
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public static function gqlTypeNameByContext(mixed $context): string
    {
        /** @var self $context */
        return $context->handle . '_GlobalSet';
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public static function gqlScopesByContext(mixed $context): array
    {
        /** @var self $context */
        return ['globalsets.' . $context->uid];
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public static function gqlMutationNameByContext(mixed $context): string
    {
        /** @var self $context */
        return 'save_' . $context->handle . '_GlobalSet';
    }

    /**
     * @var string|null Name
     */
    public ?string $name = null;

    /**
     * @var string|null Handle
     */
    public ?string $handle = null;

    /**
     * @var int|null Sort order
     * @since 3.7.0
     */
    public ?int $sortOrder = null;

    /**
     * Use the global set's name as its string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        if ($this->name) {
            return $this->name;
        }

        return parent::__toString();
    }

    /**
     * @inheritdoc
     */
    protected function defineBehaviors(): array
    {
        $behaviors = parent::defineBehaviors();
        $behaviors['fieldLayout'] = [
            'class' => FieldLayoutBehavior::class,
            'elementType' => __CLASS__,
        ];
        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return array_merge(parent::attributeLabels(), [
            'handle' => Craft::t('app', 'Handle'),
            'name' => Craft::t('app', 'Name'),
        ]);
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
            'targetClass' => GlobalSetRecord::class,
            'except' => [self::SCENARIO_ESSENTIALS],
        ];

        $rules[] = [
            ['handle'],
            HandleValidator::class,
            'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title'],
            'except' => [self::SCENARIO_ESSENTIALS],
        ];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function scenarios(): array
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_SAVE_SET] = $scenarios[self::SCENARIO_DEFAULT];

        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function getFieldLayout(): ?FieldLayout
    {
        /** @var FieldLayoutBehavior $behavior */
        $behavior = $this->getBehavior('fieldLayout');
        return $behavior->getFieldLayout();
    }

    /**
     * @inheritdoc
     */
    protected function cpEditUrl(): ?string
    {
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

        try {
            $fieldLayout = $this->getFieldLayout();
        } catch (InvalidConfigException $e) {
            $fieldLayout = null;
        }

        if ($fieldLayout !== null) {
            Craft::$app->getFields()->deleteLayout($fieldLayout);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterRestore(): void
    {
        // Restore the field layout too
        if (
            $this->fieldLayoutId &&
            !Craft::$app->getFields()->restoreLayoutById($this->fieldLayoutId)
        ) {
            Craft::warning("Global set $this->id restored, but its field layout ($this->fieldLayoutId) was not.");
        }

        parent::afterRestore();
    }

    /**
     * Returns the global set’s config.
     *
     * @return array
     * @since 3.5.0
     */
    public function getConfig(): array
    {
        $config = [
            'name' => $this->name,
            'handle' => $this->handle,
            'sortOrder' => (int)$this->sortOrder,
        ];

        $fieldLayout = $this->getFieldLayout();

        if ($fieldLayoutConfig = $fieldLayout->getConfig()) {
            $config['fieldLayouts'] = [
                $fieldLayout->uid => $fieldLayoutConfig,
            ];
        }

        return $config;
    }
}

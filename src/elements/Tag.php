<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements;

use Craft;
use craft\base\Element;
use craft\db\Table;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\conditions\tags\TagCondition;
use craft\elements\db\TagQuery;
use craft\gql\interfaces\elements\Tag as TagInterface;
use craft\helpers\Db;
use craft\models\FieldLayout;
use craft\models\TagGroup;
use craft\records\Tag as TagRecord;
use GraphQL\Type\Definition\Type;
use yii\base\InvalidConfigException;
use yii\validators\InlineValidator;

/**
 * Tag represents a tag element.
 *
 * @property TagGroup $group the tag's group
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Tag extends Element
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Tag');
    }

    /**
     * @inheritdoc
     */
    public static function lowerDisplayName(): string
    {
        return Craft::t('app', 'tag');
    }

    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('app', 'Tags');
    }

    /**
     * @inheritdoc
     */
    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('app', 'tags');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle(): ?string
    {
        return 'tag';
    }

    /**
     * @inheritdoc
     */
    public static function hasTitles(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasUris(): bool
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
     * @return TagQuery The newly created [[TagQuery]] instance.
     */
    public static function find(): TagQuery
    {
        return new TagQuery(static::class);
    }

    /**
     * @inheritdoc
     * @return TagCondition
     */
    public static function createCondition(): ElementConditionInterface
    {
        return Craft::createObject(TagCondition::class, [static::class]);
    }

    /**
     * @inheritdoc
     */
    protected static function defineSources(string $context): array
    {
        $sources = [];

        foreach (Craft::$app->getTags()->getAllTagGroups() as $tagGroup) {
            $sources[] = [
                'key' => 'taggroup:' . $tagGroup->uid,
                'label' => Craft::t('site', $tagGroup->name),
                'criteria' => ['groupId' => $tagGroup->id],
            ];
        }

        return $sources;
    }

    /**
     * Returns the GraphQL type name that tags should use, based on their tag group.
     *
     * @since 5.0.0
     */
    public static function gqlTypeName(TagGroup $tagGroup): string
    {
        return sprintf('%s_Tag', $tagGroup->handle);
    }

    /**
     * @inheritdoc
     */
    public static function baseGqlType(): Type
    {
        return TagInterface::getType();
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public static function gqlScopesByContext(mixed $context): array
    {
        /** @var TagGroup $context */
        return ['taggroups.' . $context->uid];
    }

    /**
     * @inheritdoc
     */
    protected static function defineFieldLayouts(?string $source): array
    {
        if ($source !== null) {
            $groups = [];
            if (preg_match('/^taggroup:(.+)$/', $source, $matches)) {
                $group = Craft::$app->getTags()->getTagGroupByUid($matches[1]);
                if ($group) {
                    $groups[] = $group;
                }
            }
        } else {
            $groups = Craft::$app->getTags()->getAllTagGroups();
        }

        return array_map(fn(TagGroup $group) => $group->getFieldLayout(), $groups);
    }

    /**
     * @var int|null Group ID
     */
    public ?int $groupId = null;

    /**
     * @var bool Whether the tag was deleted along with its group
     * @see beforeDelete()
     */
    public bool $deletedWithGroup = false;

    /**
     * @inheritdoc
     */
    public function extraFields(): array
    {
        $names = parent::extraFields();
        $names[] = 'group';
        return $names;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['groupId'], 'number', 'integerOnly' => true];
        $rules[] = [
            ['title'],
            'validateTitle',
            'when' => fn(): bool => !$this->hasErrors('groupId') && !$this->hasErrors('title'),
            'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE],
        ];
        return $rules;
    }

    /**
     * Validates the tag title.
     *
     * @param string $attribute
     * @param array|null $params
     * @param InlineValidator $validator
     * @since 3.4.12
     */
    public function validateTitle(string $attribute, ?array $params, InlineValidator $validator): void
    {
        $query = self::find()
            ->groupId($this->groupId)
            ->siteId($this->siteId)
            ->title(Db::escapeParam($this->title));

        if ($this->id) {
            $query->andWhere(['not', ['elements.id' => $this->id]]);
        }

        if ($query->exists()) {
            $validator->addError($this, $attribute, Craft::t('yii', '{attribute} "{value}" has already been taken.'));
        }
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    protected function cacheTags(): array
    {
        return [
            "group:$this->groupId",
        ];
    }

    /**
     * @inheritdoc
     */
    public function canView(User $user): bool
    {
        return true;
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
        return true;
    }

    /**
     * @inheritdoc
     */
    public function canDelete(User $user): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getFieldLayout(): ?FieldLayout
    {
        try {
            return $this->getGroup()->getFieldLayout();
        } catch (InvalidConfigException) {
            return null;
        }
    }

    /**
     * Returns the tag's group.
     *
     * @return TagGroup
     * @throws InvalidConfigException if [[groupId]] is missing or invalid
     */
    public function getGroup(): TagGroup
    {
        if (!isset($this->groupId)) {
            throw new InvalidConfigException('Tag is missing its group ID');
        }

        if (($group = Craft::$app->getTags()->getTagGroupById($this->groupId)) === null) {
            throw new InvalidConfigException('Invalid tag group ID: ' . $this->groupId);
        }

        return $group;
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public function getGqlTypeName(): string
    {
        return static::gqlTypeName($this->getGroup());
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function afterSave(bool $isNew): void
    {
        if (!$this->propagating) {
            // Get the tag record
            if (!$isNew) {
                $record = TagRecord::findOne($this->id);

                if (!$record) {
                    throw new InvalidConfigException("Invalid tag ID: $this->id");
                }
            } else {
                $record = new TagRecord();
                $record->id = (int)$this->id;
            }

            $record->groupId = (int)$this->groupId;
            $record->save(false);
        }

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function beforeDelete(): bool
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        // Update the tag record
        Db::update(Table::TAGS, [
            'deletedWithGroup' => $this->deletedWithGroup,
        ], [
            'id' => $this->id,
        ], [], false);

        return true;
    }
}

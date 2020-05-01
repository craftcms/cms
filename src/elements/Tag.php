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
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\TagQuery;
use craft\helpers\Db;
use craft\models\TagGroup;
use craft\records\Tag as TagRecord;
use yii\base\Exception;
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
    public static function refHandle()
    {
        return 'tag';
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
    public static function find(): ElementQueryInterface
    {
        return new TagQuery(static::class);
    }

    /**
     * @inheritdoc
     */
    protected static function defineSources(string $context = null): array
    {
        $sources = [];

        foreach (Craft::$app->getTags()->getAllTagGroups() as $tagGroup) {
            $sources[] = [
                'key' => 'taggroup:' . $tagGroup->uid,
                'label' => Craft::t('site', $tagGroup->name),
                'criteria' => ['groupId' => $tagGroup->id]
            ];
        }

        return $sources;
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public static function gqlTypeNameByContext($context): string
    {
        /** @var TagGroup $context */
        return $context->handle . '_Tag';
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public static function gqlScopesByContext($context): array
    {
        /** @var TagGroup $context */
        return ['taggroups.' . $context->uid];
    }

    /**
     * @var int|null Group ID
     */
    public $groupId;

    /**
     * @var bool Whether the tag was deleted along with its group
     * @see beforeDelete()
     */
    public $deletedWithGroup = false;

    /**
     * @inheritdoc
     */
    public function extraFields()
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
            'when' => function(): bool {
                return !$this->hasErrors('groupId') && !$this->hasErrors('title');
            },
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
    public function validateTitle(string $attribute, array $params = null, InlineValidator $validator)
    {
        $query = static::find()
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
     */
    public function getIsEditable(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getFieldLayout()
    {
        return parent::getFieldLayout() ?? $this->getGroup()->getFieldLayout();
    }

    /**
     * Returns the tag's group.
     *
     * @return TagGroup
     * @throws InvalidConfigException if [[groupId]] is missing or invalid
     */
    public function getGroup(): TagGroup
    {
        if ($this->groupId === null) {
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
        return static::gqlTypeNameByContext($this->getGroup());
    }

    // Indexes, etc.
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function getEditorHtml(): string
    {
        $html = Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'textField', [
            [
                'label' => Craft::t('app', 'Title'),
                'siteId' => $this->siteId,
                'id' => 'title',
                'name' => 'title',
                'value' => $this->title,
                'errors' => $this->getErrors('title'),
                'first' => true,
                'autofocus' => true,
                'required' => true
            ]
        ]);

        $html .= parent::getEditorHtml();

        return $html;
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     * @throws Exception if reasons
     */
    public function afterSave(bool $isNew)
    {
        if (!$this->propagating) {
            // Get the tag record
            if (!$isNew) {
                $record = TagRecord::findOne($this->id);

                if (!$record) {
                    throw new Exception('Invalid tag ID: ' . $this->id);
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
        Craft::$app->getDb()->createCommand()
            ->update(Table::TAGS, [
                'deletedWithGroup' => $this->deletedWithGroup,
            ], ['id' => $this->id], [], false)
            ->execute();

        return true;
    }
}

<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\elements;

use Craft;
use craft\base\Element;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\TagQuery;
use craft\models\TagGroup;
use craft\records\Tag as TagRecord;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * Tag represents a tag element.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Tag extends Element
{
    // Static
    // =========================================================================

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
    public static function isLocalized(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     *
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
                'key' => 'taggroup:'.$tagGroup->id,
                'label' => Craft::t('site', $tagGroup->name),
                'criteria' => ['groupId' => $tagGroup->id]
            ];
        }

        return $sources;
    }

    // Properties
    // =========================================================================

    /**
     * @var int|null Group ID
     */
    public $groupId;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['groupId'], 'number', 'integerOnly' => true];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getIsEditable(): bool
    {
        return true;
    }

    /**
     * Returns the tag's group.
     *
     * @return TagGroup
     * @throws InvalidConfigException if [[groupId]] is missing or invalid
     */
    public function getGroup()
    {
        if ($this->groupId === null) {
            throw new InvalidConfigException('Tag is missing its group ID');
        }

        if (($group = Craft::$app->getTags()->getTagGroupById($this->groupId)) === null) {
            throw new InvalidConfigException('Invalid tag group ID: '.$this->groupId);
        }

        return $group;
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
     */
    public function beforeSave(bool $isNew): bool
    {
        // Make sure the field layout is set correctly
        $this->fieldLayoutId = $this->getGroup()->fieldLayoutId;

        return parent::beforeSave($isNew);
    }

    /**
     * @inheritdoc
     * @throws Exception if reasons
     */
    public function afterSave(bool $isNew)
    {
        // Get the tag record
        if (!$isNew) {
            $record = TagRecord::findOne($this->id);

            if (!$record) {
                throw new Exception('Invalid tag ID: '.$this->id);
            }
        } else {
            $record = new TagRecord();
            $record->id = $this->id;
        }

        $record->groupId = $this->groupId;
        $record->save(false);

        parent::afterSave($isNew);
    }
}

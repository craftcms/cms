<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\behaviors;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\db\Table;
use craft\elements\User;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use yii\base\Behavior;

/**
 * DraftBehavior is applied to element drafts.
 *
 * @property Element $owner
 * @property-read ElementInterface|Element $source
 * @property-read User|null $creator
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2.0
 */
class DraftBehavior extends Behavior
{
    /**
     * @var int|null The source element’s ID
     */
    public $sourceId;

    /**
     * @var int|null The draft creator’s ID
     */
    public $creatorId;

    /**
     * @var string The draft name
     */
    public $draftName;

    /**
     * @var string|null The draft notes
     */
    public $draftNotes;

    /**
     * @var bool Whether to track changes in this draft
     */
    public $trackChanges = false;

    /**
     * @var \DateTime|null The last date that this draft was merged with changes from the source element
     */
    public $dateLastMerged = false;

    /**
     * @var bool Whether the source element’s changes are currently being merged into the draft.
     * @since 3.4.0
     */
    public $mergingChanges = false;

    /**
     * @var ElementInterface|null
     * @see _source()
     */
    private $_source;

    /**
     * @var array|null
     * @see _outdatedAttributes()
     */
    private $_outdatedAttributes;

    /**
     * @var array|null
     * @see _modifiedAttributes()
     */
    private $_modifiedAttributes;

    /**
     * @var array|null
     * @see _outdatedFields()
     */
    private $_outdatedFields;

    /**
     * @var array|null
     * @see _modifiedFields()
     */
    private $_modifiedFields;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->dateLastMerged !== null) {
            $this->dateLastMerged = DateTimeHelper::toDateTime($this->dateLastMerged);
        }
    }

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            Element::EVENT_AFTER_PROPAGATE => [$this, 'handleSave'],
            Element::EVENT_AFTER_DELETE => [$this, 'handleDelete'],
        ];
    }

    /**
     * Updates the row in the `drafts` table after the draft element is saved.
     */
    public function handleSave()
    {
        Craft::$app->getDb()->createCommand()
            ->update(Table::DRAFTS, [
                'name' => $this->draftName,
                'notes' => $this->draftNotes,
                'dateLastMerged' => Db::prepareDateForDb($this->dateLastMerged),
            ], ['id' => $this->owner->draftId], [], false)
            ->execute();
    }

    /**
     * Deletes the row in the `drafts` table after the draft element is deleted.
     */
    public function handleDelete()
    {
        Craft::$app->getDb()->createCommand()
            ->delete(Table::DRAFTS, ['id' => $this->owner->draftId])
            ->execute();
    }

    /**
     * Returns the draft’s name.
     *
     * @return string
     * @since 3.3.17
     */
    public function getDraftName(): string
    {
        return $this->draftName;
    }

    /**
     * Returns the draft’s source element.
     *
     * @return ElementInterface|null
     * @deprecated in 3.2.9. Use [[ElementHelper::sourceElement()]] instead.
     */
    public function getSource()
    {
        if (!$this->sourceId) {
            return null;
        }

        $owner = $this->owner;
        return $owner::find()
            ->id($this->sourceId)
            ->siteId($this->owner->siteId)
            ->anyStatus()
            ->one();
    }

    /**
     * Returns the draft’s creator.
     *
     * @return User|null
     */
    public function getCreator()
    {
        if (!$this->creatorId) {
            return null;
        }

        return User::find()
            ->id($this->creatorId)
            ->anyStatus()
            ->one();
    }

    /**
     * Returns whether the source element has been saved since the time this draft was
     * created or last merged.
     *
     * @return bool
     * @since 3.4.0
     */
    public function getIsOutdated(): bool
    {
        if (($source = $this->_source()) === null) {
            return false;
        }

        if ($this->owner->dateCreated > $source->dateUpdated) {
            return false;
        }

        if (!$this->trackChanges || !$this->dateLastMerged) {
            return true;
        }

        return $this->dateLastMerged < $source->dateUpdated;
    }

    /**
     * Returns the outdated attributes.
     *
     * @return string[]
     * @since 3.4.0
     */
    public function getOutdatedAttributes(): array
    {
        return array_keys($this->_outdatedAttributes());
    }

    /**
     * Returns whether an attribute on the draft has fallen behind the source element’s value.
     *
     * @param string $fieldHandle
     * @return bool
     * @since 3.4.0
     */
    public function isAttributeOutdated(string $fieldHandle): bool
    {
        return isset($this->_outdatedAttributes()[$fieldHandle]);
    }

    /**
     * Returns whether an attribute has changed on the draft.
     *
     * @param string $fieldHandle
     * @return bool
     * @since 3.4.0
     */
    public function isAttributeModified(string $fieldHandle): bool
    {
        return isset($this->_modifiedAttributes()[$fieldHandle]);
    }

    /**
     * Returns the outdated field handles.
     *
     * @return string[]
     * @since 3.4.0
     */
    public function getOutdatedFields(): array
    {
        return array_keys($this->_outdatedFields());
    }

    /**
     * Returns whether a field value on the draft has fallen behind the source element’s value.
     *
     * @param string $fieldHandle
     * @return bool
     * @since 3.4.0
     */
    public function isFieldOutdated(string $fieldHandle): bool
    {
        return isset($this->_outdatedFields()[$fieldHandle]);
    }

    /**
     * Returns whether a field value has changed on the draft.
     *
     * @param string $fieldHandle
     * @return bool
     * @since 3.4.0
     */
    public function isFieldModified(string $fieldHandle): bool
    {
        return isset($this->_modifiedFields()[$fieldHandle]);
    }

    /**
     * Returns the source element.
     *
     * @return ElementInterface|Element|null
     * @since 3.4.0
     */
    private function _source()
    {
        if (!$this->sourceId) {
            return null;
        }

        if ($this->_source !== null) {
            return $this->_source;
        }

        return $this->_source = ElementHelper::sourceElement($this->owner);
    }

    /**
     * @return array The attribute names that have been modified for this draft
     */
    private function _outdatedAttributes(): array
    {
        if (!$this->sourceId || !$this->trackChanges) {
            return [];
        }

        if ($this->_outdatedAttributes !== null) {
            return $this->_outdatedAttributes;
        }

        $query = (new Query())
            ->select(['attribute'])
            ->from([Table::CHANGEDATTRIBUTES])
            ->where([
                'elementId' => $this->sourceId,
                'siteId' => $this->owner->siteId,
            ]);

        if ($this->dateLastMerged) {
            $query->andWhere(['>=', 'dateUpdated', Db::prepareDateForDb($this->dateLastMerged)]);
        } else {
            $query->andWhere(['>=', 'dateUpdated', Db::prepareDateForDb($this->owner->dateCreated)]);
        }

        return $this->_outdatedAttributes = array_flip($query->column());
    }

    /**
     * @return array The attribute names that have been modified for this draft
     */
    private function _modifiedAttributes(): array
    {
        if (!$this->trackChanges) {
            return [];
        }

        if ($this->_modifiedAttributes !== null) {
            return $this->_modifiedAttributes;
        }

        return $this->_modifiedAttributes = array_flip((new Query())
            ->select(['attribute'])
            ->from([Table::CHANGEDATTRIBUTES])
            ->where([
                'elementId' => $this->owner->id,
                'siteId' => $this->owner->siteId,
            ])
            ->column());
    }

    /**
     * @return array The field handles that have been modified for this draft
     */
    private function _outdatedFields(): array
    {
        if (!$this->sourceId || !$this->trackChanges) {
            return [];
        }

        if ($this->_outdatedFields !== null) {
            return $this->_outdatedFields;
        }

        $query = (new Query())
            ->select(['f.handle'])
            ->from(['f' => Table::FIELDS])
            ->innerJoin(Table::CHANGEDFIELDS . ' cf', '[[cf.fieldId]] = [[f.id]]')
            ->where([
                'cf.elementId' => $this->sourceId,
                'cf.siteId' => $this->owner->siteId,
            ]);

        if ($this->dateLastMerged) {
            $query->andWhere(['>=', 'cf.dateUpdated', Db::prepareDateForDb($this->dateLastMerged)]);
        } else {
            $query->andWhere(['>=', 'cf.dateUpdated', Db::prepareDateForDb($this->owner->dateCreated)]);
        }

        return $this->_outdatedFields = array_flip($query->column());
    }

    /**
     * @return array The field handles that have been modified for this draft
     */
    private function _modifiedFields(): array
    {
        if (!$this->trackChanges) {
            return [];
        }

        if ($this->_modifiedFields !== null) {
            return $this->_modifiedFields;
        }

        return $this->_modifiedFields = array_flip((new Query())
            ->select(['f.handle'])
            ->from(['f' => Table::FIELDS])
            ->innerJoin(Table::CHANGEDFIELDS . ' cf', '[[cf.fieldId]] = [[f.id]]')
            ->where([
                'cf.elementId' => $this->owner->id,
                'cf.siteId' => $this->owner->siteId,
            ])
            ->column());
    }
}

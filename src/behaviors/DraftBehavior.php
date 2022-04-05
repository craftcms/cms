<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\behaviors;

use craft\base\Element;
use craft\db\Table;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use DateTime;

/**
 * DraftBehavior is applied to element drafts.
 *
 * @property-read Datetime|null $dateLastMerged The date that the canonical element was last merged into this one
 * @property-read bool $mergingChanges Whether recent changes to the canonical element are being merged into this element
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2.0
 */
class DraftBehavior extends BaseRevisionBehavior
{
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
     * @deprecated in 3.7.0.
     */
    public $trackChanges = true;

    /**
     * @var bool Whether the draft should be marked as saved (if unpublished).
     * @since 3.6.6
     */
    public $markAsSaved = true;

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
        Db::update(Table::DRAFTS, [
            'provisional' => $this->owner->isProvisionalDraft,
            'name' => $this->draftName,
            'notes' => $this->draftNotes,
            'dateLastMerged' => Db::prepareDateForDb($this->owner->dateLastMerged),
            'saved' => $this->markAsSaved,
        ], [
            'id' => $this->owner->draftId,
        ], [], false);
    }

    /**
     * Deletes the row in the `drafts` table after the draft element is deleted.
     */
    public function handleDelete()
    {
        if ($this->owner->hardDelete) {
            Db::delete(Table::DRAFTS, [
                'id' => $this->owner->draftId,
            ]);
        }
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
     * Returns whether the source element has been saved since the time this draft was
     * created or last merged.
     *
     * @return bool
     * @since 3.4.0
     * @deprecated in 3.7.12. Use [[ElementHelper::isOutdated()]] instead.
     */
    public function getIsOutdated(): bool
    {
        return ElementHelper::isOutdated($this->owner);
    }

    /**
     * Returns the outdated attributes.
     *
     * @return string[]
     * @since 3.4.0
     * @deprecated in 3.7.0. Use [[\craft\base\ElementInterface::getOutdatedAttributes()]] instead.
     */
    public function getOutdatedAttributes(): array
    {
        return $this->owner->getOutdatedAttributes();
    }

    /**
     * Returns whether an attribute on the draft has fallen behind the source element’s value.
     *
     * @param string $name
     * @return bool
     * @since 3.4.0
     * @deprecated in 3.7.0. Use [[\craft\base\ElementInterface::isAttributeOutdated()]] instead.
     */
    public function isAttributeOutdated(string $name): bool
    {
        return $this->owner->isAttributeOutdated($name);
    }

    /**
     * Returns whether an attribute has changed on the draft.
     *
     * @param string $name
     * @return bool
     * @since 3.4.0
     * @deprecated in 3.7.0. Use [[\craft\base\ElementInterface::isAttributeModified()]] instead.
     */
    public function isAttributeModified(string $name): bool
    {
        return $this->owner->isAttributeModified($name);
    }

    /**
     * Returns the outdated field handles.
     *
     * @return string[]
     * @since 3.4.0
     * @deprecated in 3.7.0. Use [[\craft\base\ElementInterface::getOutdatedFields()]] instead.
     */
    public function getOutdatedFields(): array
    {
        return $this->owner->getOutdatedFields();
    }

    /**
     * Returns whether a field value on the draft has fallen behind the source element’s value.
     *
     * @param string $fieldHandle
     * @return bool
     * @since 3.4.0
     * @deprecated in 3.7.0. Use [[\craft\base\ElementInterface::isFieldOutdated()]] instead.
     */
    public function isFieldOutdated(string $fieldHandle): bool
    {
        return $this->owner->isFieldOutdated($fieldHandle);
    }

    /**
     * Returns whether a field value has changed on the draft.
     *
     * @param string $fieldHandle
     * @return bool
     * @since 3.4.0
     * @deprecated in 3.7.0. Use [[\craft\base\ElementInterface::isFieldModified()]] instead.
     */
    public function isFieldModified(string $fieldHandle): bool
    {
        return $this->owner->isFieldModified($fieldHandle);
    }

    /**
     * Returns the date that the canonical element was last merged into this one.
     *
     * @since 3.7.0
     * @deprecated in 3.7.0. Use [[\craft\base\ElementInterface::$dateLastMerged]] instead.
     */
    public function getDateLastMerged(): ?DateTime
    {
        return $this->owner->dateLastMerged;
    }

    /**
     * Returns whether recent changes to the canonical element are being merged into this element.
     *
     * @since 3.7.0
     * @deprecated in 3.7.0. Use [[\craft\base\ElementInterface::$mergingCanonicalChanges]] instead.
     */
    public function getMergingChanges(): bool
    {
        return $this->owner->mergingCanonicalChanges;
    }
}

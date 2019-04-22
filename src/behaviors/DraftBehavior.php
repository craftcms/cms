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
use craft\db\Table;
use craft\elements\User;
use yii\base\Behavior;

/**
 * DraftBehavior is applied to element drafts.
 *
 * @property Element $owner
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2
 */
class DraftBehavior extends Behavior
{
    /**
     * @var int The source element’s ID
     */
    public $sourceId;

    /**
     * @var int The draft creator’s ID
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
     * @inheritdoc
     */
    public function events()
    {
        return [
            Element::EVENT_AFTER_DELETE => [$this, 'handleDelete'],
        ];
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
     * Returns the draft’s source element.
     *
     * @return ElementInterface
     */
    public function getSource(): ElementInterface
    {
        return $this->owner::find()
            ->id($this->sourceId)
            ->siteId($this->owner->siteId)
            ->anyStatus()
            ->one();
    }

    /**
     * Returns the draft’s creator.
     *
     * @return User
     */
    public function getCreator(): User
    {
        return User::find()
            ->id($this->creatorId)
            ->anyStatus()
            ->one();
    }
}

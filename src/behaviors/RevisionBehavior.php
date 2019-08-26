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
use craft\helpers\StringHelper;
use yii\base\Behavior;
use yii\base\Model;
use yii\validators\UrlValidator;

/**
 * RevisionBehavior is applied to element revisions.
 *
 * @property ElementInterface|Element $owner
 * @property-read ElementInterface|Element $source
 * @property-read User $creator
 * @property-read string $revisionLabel
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2
 */
class RevisionBehavior extends Behavior
{
    /**
     * @var int The source element’s ID
     */
    public $sourceId;

    /**
     * @var int|null The revision creator’s ID
     */
    public $creatorId;

    /**
     * @var int The revision number
     */
    public $revisionNum;

    /**
     * @var string|null The revision notes
     */
    public $revisionNotes;

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
            ->delete(Table::REVISIONS, ['id' => $this->owner->revisionId])
            ->execute();
    }

    /**
     * Returns the revision’s source element.
     *
     * @return ElementInterface
     * @deprecated in 3.2.9. Use [[ElementInterface::getSource()]] instead.
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
     * Returns the revision’s creator.
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
     * Returns the revision label.
     *
     * @return string
     */
    public function getRevisionLabel(): string
    {
        return Craft::t('app', 'Revision {num}', [
            'num' => $this->revisionNum,
        ]);
    }
}

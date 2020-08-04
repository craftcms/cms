<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\behaviors;

use Craft;
use craft\base\Element;
use craft\db\Table;
use craft\helpers\Db;

/**
 * RevisionBehavior is applied to element revisions.
 *
 * @property-read string $revisionLabel The revision label
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2.0
 */
class RevisionBehavior extends BaseRevisionBehavior
{
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
        Db::delete(Table::REVISIONS, [
            'id' => $this->owner->revisionId,
        ]);
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

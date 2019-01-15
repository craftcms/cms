<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\records;

use craft\db\ActiveRecord;
use craft\db\Table;
use yii\db\ActiveQueryInterface;

/**
 * Stores entry drafts.
 *
 * @property int $id ID
 * @property int $entryId Entry ID
 * @property int $sectionId Section ID
 * @property int $creatorId Creator ID
 * @property int $siteId Site ID
 * @property string $name Name
 * @property string $notes Notes
 * @property array $data Data
 * @property Entry $entry Entry
 * @property Section $section Section
 * @property User $creator Creator
 * @property Site $site Site
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class EntryDraft extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return Table::ENTRYDRAFTS;
    }

    /**
     * Returns the entry draft’s entry.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getEntry(): ActiveQueryInterface
    {
        return $this->hasOne(Entry::class, ['id' => 'entryId']);
    }

    /**
     * Returns the entry draft’s section.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getSection(): ActiveQueryInterface
    {
        return $this->hasOne(Section::class, ['id' => 'sectionId']);
    }

    /**
     * Returns the entry draft’s creator.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getCreator(): ActiveQueryInterface
    {
        return $this->hasOne(User::class, ['id' => 'creatorId']);
    }

    /**
     * Returns the associated site.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getSite(): ActiveQueryInterface
    {
        return $this->hasOne(Site::class, ['id' => 'siteId']);
    }
}

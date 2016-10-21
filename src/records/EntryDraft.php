<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\records;

use yii\db\ActiveQueryInterface;
use craft\app\db\ActiveRecord;

/**
 * Stores entry drafts.
 *
 * @property integer $id        ID
 * @property integer $entryId   Entry ID
 * @property integer $sectionId Section ID
 * @property integer $creatorId Creator ID
 * @property integer $siteId    Site ID
 * @property string  $name      Name
 * @property string  $notes     Notes
 * @property array   $data      Data
 * @property Entry   $entry     Entry
 * @property Section $section   Section
 * @property User    $creator   Creator
 * @property Site    $site      Site
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class EntryDraft extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{%entrydrafts}}';
    }

    /**
     * Returns the entry draft’s entry.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getEntry()
    {
        return $this->hasOne(Entry::class, ['id' => 'entryId']);
    }

    /**
     * Returns the entry draft’s section.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getSection()
    {
        return $this->hasOne(Section::class, ['id' => 'sectionId']);
    }

    /**
     * Returns the entry draft’s creator.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getCreator()
    {
        return $this->hasOne(User::class, ['id' => 'creatorId']);
    }

    /**
     * Returns the associated site.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getSite()
    {
        return $this->hasOne(Site::class, ['id' => 'siteId']);
    }
}

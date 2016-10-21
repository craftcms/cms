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
 * Class Entry record.
 *
 * @property integer        $id         ID
 * @property integer        $sectionId  Section ID
 * @property integer        $typeId     Type ID
 * @property integer        $authorId   Author ID
 * @property \DateTime      $postDate   Post date
 * @property \DateTime      $expiryDate Expiry date
 * @property Element        $element    Element
 * @property Section        $section    Section
 * @property EntryType      $type       Type
 * @property User           $author     Author
 * @property EntryVersion[] $versions   Versions
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Entry extends ActiveRecord
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
        return '{{%entries}}';
    }

    /**
     * Returns the entry’s element.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getElement()
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }

    /**
     * Returns the entry’s section.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getSection()
    {
        return $this->hasOne(Section::class, ['id' => 'sectionId']);
    }

    /**
     * Returns the entry’s type.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getType()
    {
        return $this->hasOne(EntryType::class, ['id' => 'typeId']);
    }

    /**
     * Returns the entry’s author.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getAuthor()
    {
        return $this->hasOne(User::class, ['id' => 'authorId']);
    }

    /**
     * Returns the entry’s versions.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getVersions()
    {
        return $this->hasMany(EntryVersion::class, ['elementId' => 'id']);
    }
}

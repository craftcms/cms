<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\records;

use craft\db\ActiveRecord;
use craft\db\Table;

/**
 * Element record class.
 *
 * @property int $id ID
 * @property int|null $draftId Draft ID
 * @property int|null $revisionId Revision ID
 * @property int|null $fieldLayoutId Field layout ID
 * @property string $type Type
 * @property bool $enabled Enabled
 * @property bool $archived Archived
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Element extends ActiveRecord
{
    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return Table::ELEMENTS;
    }
}

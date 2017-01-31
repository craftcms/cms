<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\records;

use craft\db\ActiveRecord;

/**
 * Class Site record.
 *
 * @property int    $id        ID
 * @property string $name      Name
 * @property string $handle    Handle
 * @property string $language  Language
 * @property bool   $hasUrls   Has URLs
 * @property bool   $baseUrl   Base URL
 * @property int    $sortOrder Sort order
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Site extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%sites}}';
    }
}

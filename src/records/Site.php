<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\records;

use craft\app\db\ActiveRecord;

/**
 * Class Site record.
 *
 * @property integer $id        ID
 * @property string  $name      Name
 * @property string  $handle    Handle
 * @property string  $language  Language
 * @property boolean $hasUrls   Has URLs
 * @property boolean $baseUrl   Base URL
 * @property integer $sortOrder Sort order
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
    public static function tableName()
    {
        return '{{%sites}}';
    }
}

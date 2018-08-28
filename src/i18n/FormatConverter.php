<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\i18n;

use Craft;

/**
 * @inheritdoc
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.6
 */
class FormatConverter extends \yii\helpers\FormatConverter
{
    /**
     * @inheritdoc
     */
    public static function convertDatePhpToIcu($pattern)
    {
        // Special cases for standalone values
        switch ($pattern) {
            // month names
            case 'n':
                return 'L';
            case 'm':
                return 'LL';
            case 'M':
                return 'LLL';
            case 'F':
                return 'LLLL';
            // week day names
            case 'D':
                return 'ccc';
            case 'l':
                return 'cccc';
        }

        return parent::convertDatePhpToIcu($pattern);
    }
}

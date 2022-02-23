<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\i18n;

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
    public static function convertDatePhpToIcu($pattern): string
    {
        // Special cases for standalone values
        return match ($pattern) {
            'n' => 'L',
            'm' => 'LL',
            'M' => 'LLL',
            'F' => 'LLLL',
            'D' => 'ccc',
            'l' => 'cccc',
            default => parent::convertDatePhpToIcu($pattern),
        };
    }
}

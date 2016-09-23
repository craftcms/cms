<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\helpers;

use HTMLPurifier_Config;

/**
 * HtmlPurifier provides an ability to clean up HTML from any harmful code.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class HtmlPurifier extends \yii\helpers\HtmlPurifier
{
    /**
     * @param string $string
     *
     * @return string
     */
    public static function cleanUtf8($string)
    {
        return \HTMLPurifier_Encoder::cleanUTF8($string);
    }

    /**
     * @param string $string
     * @param HTMLPurifier_Config $config
     *
     * @return string
     */
    public static function convertToUtf8($string, $config)
    {
        return \HTMLPurifier_Encoder::convertToUTF8($string, $config, null);
    }
}

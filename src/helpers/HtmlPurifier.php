<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\helpers;

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
    public static function cleanUtf8(string $string): string
    {
        return \HTMLPurifier_Encoder::cleanUTF8($string);
    }

    /**
     * @param string              $string
     * @param HTMLPurifier_Config $config
     *
     * @return string
     */
    public static function convertToUtf8(string $string, HTMLPurifier_Config $config): string
    {
        return \HTMLPurifier_Encoder::convertToUTF8($string, $config, null);
    }
}

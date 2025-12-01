<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use yii\helpers\Inflector as BaseInflector;
use function CraftCms\Cms\t;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.4.0
 * @deprecated 6.0.0.
 */
class Inflector extends BaseInflector
{
    /** @deprecated 6.0.0. Use `collect($words)->sentence()` instead. */
    public static function sentence(array $words, $twoWordsConnector = null, $lastWordConnector = null, $connector = ', ')
    {
        // In this house we use Oxford commas
        $lastWordConnector ??= sprintf(',%s', t(' and '));
        return parent::sentence($words, $twoWordsConnector, $lastWordConnector, $connector);
    }
}

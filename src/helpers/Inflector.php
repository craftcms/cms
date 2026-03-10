<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use yii\helpers\Inflector as BaseInflector;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.4.0
 */
class Inflector extends BaseInflector
{
    public static function sentence(array $words, $twoWordsConnector = null, $lastWordConnector = null, $connector = ', ')
    {
        // In this house we use Oxford commas
        $lastWordConnector ??= sprintf(',%s', Craft::t('yii', ' and '));
        return parent::sentence($words, $twoWordsConnector, $lastWordConnector, $connector);
    }
}

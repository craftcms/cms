<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\config;

use yii\base\Object;

/**
 * APC config class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class ApcConfig extends Object
{
    // Properties
    // =========================================================================

    /**
     * @var bool Whether to use [[http://pecl.php.net/package/apcu APCu]] rather than [[http://pecl.php.net/package/apc APC]] as the underlying caching extension.
     */
    public $useApcu = false;
}

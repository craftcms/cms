<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\cache;

/**
 * ApcCache provides APC caching in terms of an application component.
 *
 * The caching is based on [APC](http://www.php.net/apc). To use this application component, the APC PHP extension
 * must be loaded.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class ApcCache extends \yii\caching\ApcCache
{

}

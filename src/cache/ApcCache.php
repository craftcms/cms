<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\cache;

/**
 * ApcCache provides APC caching in terms of an application component.
 *
 * The caching is based on [APC](http://www.php.net/apc). To use this application component, the APC PHP extension
 * must be loaded.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ApcCache extends \yii\caching\ApcCache
{

}

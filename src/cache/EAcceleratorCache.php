<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\cache;

/**
 * EAcceleratorCache implements a cache application module based on [eaccelerator](http://eaccelerator.net/).
 *
 * To use this application component, the eAccelerator PHP extension must be loaded.
 *
 * Please note that as of v0.9.6, eAccelerator no longer supports data caching.
 *
 * This means if you still want to use this component, your eAccelerator should be of 0.9.5.x or lower version.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class EAcceleratorCache extends \CEAcceleratorCache
{

}

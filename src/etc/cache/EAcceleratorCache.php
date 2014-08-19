<?php
namespace Craft;

/**
 * EAcceleratorCache implements a cache application module based on [eaccelerator](http://eaccelerator.net/).
 *
 * To use this application component, the eAccelerator PHP extension must be loaded.
 *
 * Please note that as of v0.9.6, eAccelerator no longer supports data caching.
 *
 * This means if you still want to use this component, your eAccelerator should be of 0.9.5.x or lower version.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.cache
 * @since     2.0
 */
class EAcceleratorCache extends \CEAcceleratorCache
{

}

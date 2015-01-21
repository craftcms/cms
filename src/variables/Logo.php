<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\variables;

use Craft;
use craft\app\helpers\IOHelper;
use craft\app\helpers\UrlHelper;

\Craft::$app->requireEdition(\Craft::Client);

/**
 * Class Logo variable.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Logo extends Image
{
	// Public Methods
	// =========================================================================

	/**
	 * Return the URL to the logo.
	 *
	 * @return string|null
	 */
	public function getUrl()
	{
		return UrlHelper::getResourceUrl('logo/'.IOHelper::getFileName($this->path));
	}
}

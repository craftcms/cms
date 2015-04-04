<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\log;

use craft\app\helpers\LoggingHelper;

/**
 * Class FileTarget
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FileTarget extends \yii\log\FileTarget
{
	/**
	 * Generates the context information to be logged and removes any sensitive info that might have
	 * been in post.
	 *
	 * @return string The context information.
	 */
	protected function getContextMessage()
	{
		$message = parent::getContextMessage();

		// Remove any sensitive info.
		return LoggingHelper::redact($message);
	}
}

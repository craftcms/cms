<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\base;

/**
 * ToolInterface defines the common interface to be implemented by tool classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
interface ToolInterface extends ComponentInterface
{
	// Static
	// =========================================================================

	/**
	 * Returns the tool’s icon value.
	 *
	 * @return string The tool’s icon value
	 */
	public static function iconValue();

	/**
	 * Returns the tool’s options HTML.
	 *
	 * @return string The tool’s options HTML
	 */
	public static function optionsHtml();

	/**
	 * Returns the tool’s button label.
	 *
	 * @return string The tool’s button label
	 */
	public static function buttonLabel();

	// Public Methods
	// =========================================================================

	/**
	 * Performs the tool’s action.
	 *
	 * @param array $params The parameters that were sent with the request
	 * @return array The response array
	 */
	public function performAction($params = []);
}

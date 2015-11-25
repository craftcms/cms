<?php
namespace Craft;

/**
 * Element Action Event class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.events
 * @since     1.0
 */
class ElementActionEvent extends Event
{
	// Properties
	// =========================================================================

	/**
	 * @var bool The message that should be displayed in the Control Panel if {@link $performAction} is false
	 */
	public $message;
}

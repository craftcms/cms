<?php
namespace Craft;

/**
 * Class TaskStatus
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.enums
 * @since     2.0
 */
abstract class TaskStatus
{
	////////////////////
	// CONSTANTS
	////////////////////

	const Pending = 'pending';
	const Running = 'running';
	const Error   = 'error';
}

<?php
namespace Craft;

/**
 * Class EmailerType
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.enums
 * @since     1.0
 */
abstract class EmailerType extends BaseEnum
{
	const Php      = 'php';
	const Sendmail = 'sendmail';
	const Smtp     = 'smtp';
	const Pop      = 'pop';
	const Gmail    = 'gmail';
}

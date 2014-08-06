<?php
namespace Craft;

/**
 * Class ElementType
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.enums
 * @since     1.0
 */
abstract class ElementType extends BaseEnum
{
	////////////////////
	// CONSTANTS
	////////////////////

	const Asset       = 'Asset';
	const Category    = 'Category';
	const Entry       = 'Entry';
	const GlobalSet   = 'GlobalSet';
	const MatrixBlock = 'MatrixBlock';
	const Tag         = 'Tag';
	const User        = 'User';
}

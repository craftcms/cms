<?php
namespace Craft;

/**
 * Class ElementType
 *
 * @abstract
 * @package craft.app.enums
 */
abstract class ElementType extends BaseEnum
{
	const Asset       = 'Asset';
	const Category    = 'Category';
	const Entry       = 'Entry';
	const GlobalSet   = 'GlobalSet';
	const MatrixBlock = 'MatrixBlock';
	const Tag         = 'Tag';
	const User        = 'User';
}

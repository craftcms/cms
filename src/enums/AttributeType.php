<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\enums;

/**
 * The AttributeType class is an abstract class that defines all of the attribute types that are available in Craft.
 * Attribute types are used primarily in Records and Models.
 *
 * This class is a poor man's version of an enum, since PHP does not have support for native enumerations.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class AttributeType extends BaseEnum
{
	// Constants
	// =========================================================================

	const Mixed      = 'mixed';
	const Bool       = 'bool';
	const ClassName  = 'classname';
	const DateTime   = 'datetime';
	const Email      = 'email';
	const Enum       = 'enum';
	const Handle     = 'handle';
	const Locale     = 'locale';
	const Name       = 'name';
	const Number     = 'number';
	const Slug       = 'slug';
	const SortOrder  = 'sortorder';
	const String     = 'string';
	const Template   = 'template';
	const Url        = 'url';
	const UrlFormat  = 'urlformat';
	const Uri        = 'uri';
}

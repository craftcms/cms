<?php
namespace Craft;

/**
 * Class AttributeType
 *
 * @package craft.app.enums
 */
abstract class AttributeType extends BaseEnum
{
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

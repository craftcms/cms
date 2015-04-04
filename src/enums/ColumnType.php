<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\enums;

/**
 * The ColumnType class is an abstract class that defines all of the abstract column types that will eventually be
 * changed to a physical database column type.
 *
 * This class is a poor man's version of an enum, since PHP does not have support for native enumerations.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class ColumnType extends BaseEnum
{
	// Constants
	// =========================================================================

	const PK           = 'pk';
	const Char         = 'char';
	const Varchar      = 'varchar';
	const TinyText     = 'tinytext';
	const Text         = 'text';
	const MediumText   = 'mediumtext';
	const LongText     = 'longtext';
	const TinyInt      = 'tinyint';
	const SmallInt     = 'smallint';
	const MediumInt    = 'mediumint';
	const Int          = 'integer';
	const BigInt       = 'bigint';
	const Decimal      = 'decimal';
	const Bool         = 'bool';
	const DateTime     = 'datetime';
	const Timestamp    = 'timestamp';
	const Time         = 'time';
	const Date         = 'date';
	const Binary       = 'binary';
	const Enum         = 'enum';
	const Locale       = 'locale';
}

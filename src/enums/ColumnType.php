<?php
namespace Craft;

/**
 * Used for representing an abstract column type that will eventually be changed
 * into a physical column type.
 *
 * Using MySQL for an example:
 *
 * * pk: an auto-incremental primary key type, will be converted into
 *       "int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY"
 *
 * * string: string type, will be converted into "varchar(255)"
 *
 * * text: a long string type, will be converted into "text"
 *
 * * integer: integer type, will be converted into "int(11)"
 *
 * * boolean: boolean type, will be converted into "tinyint(1)"
 *
 * * float: float number type, will be converted into "float"
 *
 * * decimal: decimal number type, will be converted into "decimal"
 *
 * * datetime: datetime type, will be converted into "datetime"
 *
 * * timestamp: timestamp type, will be converted into "timestamp"
 *
 * * time: time type, will be converted into "time"
 *
 * * date: date type, will be converted into "date"
 *
 * * binary: binary data type, will be converted into "blob"
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.enums
 * @since     1.0
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

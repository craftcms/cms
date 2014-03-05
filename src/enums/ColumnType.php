<?php
namespace Craft;

/**
 * Used for representing an abstract column type that will eventually be changed into a physical column type.
 *
 * Using MySQL for an example:
 * pk: an auto-incremental primary key type, will be converted into "int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY"</li>
 * string: string type, will be converted into "varchar(255)"</li>
 * text: a long string type, will be converted into "text"</li>
 * integer: integer type, will be converted into "int(11)"</li>
 * boolean: boolean type, will be converted into "tinyint(1)"</li>
 * float: float number type, will be converted into "float"</li>
 * decimal: decimal number type, will be converted into "decimal"</li>
 * datetime: datetime type, will be converted into "datetime"</li>
 * timestamp: timestamp type, will be converted into "timestamp"</li>
 * time: time type, will be converted into "time"</li>
 * date: date type, will be converted into "date"</li>
 * binary: binary data type, will be converted into "blob"</li>
 *
*/

abstract class ColumnType extends BaseEnum
{
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

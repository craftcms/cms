<?php

/**
 *
 */
class bDatabaseHelper
{
	/**
	 * Default attribute settings
	 */
	protected static $attributeTypeDefaults = array(
		bAttributeType::Char => array('maxLength'=>255),
		bAttributeType::Varchar => array('maxLength'=>255),
		bAttributeType::TinyInt => array('maxLength'=>4),
		bAttributeType::SmallInt => array('maxLength'=>6),
		bAttributeType::MediumInt => array('maxLength'=>9),
		bAttributeType::Int => array('maxLength'=>11),
		bAttributeType::BigInt => array('maxLength'=>20),
		bAttributeType::TinyInt => array('maxLength'=>4),
		bAttributeType::Boolean => array('type'=>bAttributeType::TinyInt, 'maxLength'=>1, 'unsigned'=>true, 'required'=>true, 'default'=>false),
		bAttributeType::Enum => array('values'=>array()),

		// Common model attribute types
		bAttributeType::ClassName => array('type'=>bAttributeType::Char, 'maxLength'=>150, 'required'=>true),
		bAttributeType::Handle => array('type'=>bAttributeType::Char, 'maxLength'=>100, 'required'=>true),
		bAttributeType::LanguageCode => array('type'=>bAttributeType::Char, 'maxLength'=>5, 'required'=>true),
		bAttributeType::Name => array('type'=>bAttributeType::Varchar, 'maxLength'=>100, 'required'=>true),
		bAttributeType::SortOrder => array('type'=>bAttributeType::SmallInt, 'required'=>true, 'unsigned'=>true),
		bAttributeType::Template => array('type'=>bAttributeType::Varchar, 'maxLength'=>500),
		bAttributeType::Version => array('type'=>bAttributeType::Char, 'maxLength'=>15, 'required'=>true),
	);

	/**
	 * Normalize attribute settings
	 */
	public static function normalizeAttributeSettings($settings)
	{
		if (is_string($settings))
			$settings = array('type' => $settings);
		else if (! isset($settings['type']))
			$settings['type'] = bAttributeType::Varchar;

		// Merge in the default settings
		if (isset(self::$attributeTypeDefaults[$settings['type']]))
		{
			$settings = array_merge(self::$attributeTypeDefaults[$settings['type']], $settings);

			// Override the type if the default settings specifies it
			if (isset(self::$attributeTypeDefaults[$settings['type']]['type']))
			{
				$newType = self::$attributeTypeDefaults[$settings['type']]['type'];
				$settings['type'] = $newType;

				// ...And merge in the new type's settings...
				$settings = self::normalizeAttributeSettings($settings);
			}
		}

		return $settings;
	}

	/**
	 * Generates the column definition SQL for a column
	 * @param array $settings The column settings
	 * @return string The column definition SQL
	 * @static
	 */
	public static function generateColumnDefinition($settings)
	{
		$settings = self::normalizeAttributeSettings($settings);

		// Start the column definition
		switch ($settings['type'])
		{
			case bAttributeType::Char:
				$def = 'CHAR('.$settings['maxLength'].')';
				break;
			case bAttributeType::Varchar:
				$def = 'VARCHAR('.$settings['maxLength'].')';
				break;
			case bAttributeType::TinyInt:
				$def = 'TINYINT('.$settings['maxLength'].')';
				break;
			case bAttributeType::SmallInt:
				$def = 'SMALLINT('.$settings['maxLength'].')';
				break;
			case bAttributeType::MediumInt:
				$def = 'MEDIUMINT('.$settings['maxLength'].')';
				break;
			case bAttributeType::Int:
				$def = 'INT('.$settings['maxLength'].')';
				break;
			case bAttributeType::BigInt:
				$def = 'BIGINT('.$settings['maxLength'].')';
				break;
			case bAttributeType::Float:
				$def = 'FLOAT('.$settings['maxLength'].')';
				break;
			case bAttributeType::Decimal:
				$def = 'DECIMAL('.$settings['maxLength'].')';
				break;
			case bAttributeType::Enum:
				$def = 'ENUM(';
				$values = is_array($settings['values']) ? $settings['values'] : explode(',', $settings['values']);
				foreach ($values as $i => $value)
				{
					if ($i > 0) $def .= ',';
					$def .= '\''.addslashes($value).'\'';
				}
				$def .= ')';
				break;
			default:
				$def = $settings['type'];
		}

		if (isset($settings['unsigned']) && $settings['unsigned'] === true)
			$def .= ' UNSIGNED';

		if (isset($settings['zerofill']) && $settings['zerofill'] === true)
			$def .= ' ZEROFILL';

		if (isset($settings['required']) && $settings['required'] === true)
			$def .= ' NOT NULL';
		else
			$def .= ' NULL';

		if (isset($settings['default']))
		{
			if (is_string($settings['default']) && !is_numeric($settings['default']))
				$def .= ' DEFAULT "'.$settings['default'].'"';
			else
				$def .= ' DEFAULT '.(int)$settings['default'];
		}

		return $def;
	}

	/**
	 * @param $tableName
	 * @static
	 */
	public static function createInsertAuditTrigger($tableName)
	{
		$dbName = Blocks::app()->getDbConfig('database');

		Blocks::app()->db->createCommand(
							'CREATE
							 TRIGGER `'.$dbName.'`.`'.Blocks::app()->getDbConfig('tablePrefix').'_auditinfoinsert_'.$tableName.'`
							 BEFORE INSERT ON `'.$dbName.'`.`{{'.$tableName.'}}`
							 FOR EACH ROW
							 SET NEW.date_created = UNIX_TIMESTAMP(),
								 NEW.date_updated = UNIX_TIMESTAMP(),
								 NEW.uid = UUID();
								 END;
								 SQL;'
					)->execute();
	}

	/**
	 * @param $tableName
	 * @static
	 */
	public static function createUpdateAuditTrigger($tableName)
	{
		$dbName = Blocks::app()->getDbConfig('database');

		Blocks::app()->db->createCommand(
							'CREATE
							 TRIGGER `'.$dbName.'`.`'.Blocks::app()->getDbConfig('tablePrefix').'_auditinfoupdate_'.$tableName.'`
							 BEFORE UPDATE ON `'.$dbName.'`.`{{'.$tableName.'}}`
							 FOR EACH ROW
							 SET NEW.date_updated = UNIX_TIMESTAMP(),
								 NEW.date_created = OLD.date_created;
							 END;
							 SQL;'
					)->execute();
	}
}

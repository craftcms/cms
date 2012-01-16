<?php

/**
 *
 */
class DatabaseHelper
{
	/**
	 * Generates the column definition SQL for a column
	 * @param array $settings The column settings
	 * @return string The column definition SQL
	 * @static
	 */
	public static function generateColumnDefinition($settings)
	{
		if (is_string($settings))
			return $settings;

		if (! isset($settings['type']))
		{
			if (isset($settings[0]))
				$settings['type'] = $settings[0];
			else
				$settings['type'] = AttributeType::String;
		}

		$def = $settings['type'];

		// override the col type it has a custom max length
		if (isset($settings['maxLength']))
		{
			switch ($settings['type'])
			{
				case AttributeType::String:
					$def = 'VARCHAR('.$settings['maxLength'].')';
					break;
				case AttributeType::Integer:
					$def = 'INT('.$settings['maxLength'].')';
					break;
				case AttributeType::Float:
					$def = 'FLOAT('.$settings['maxLength'].')';
					break;
				case AttributeType::Decimal:
					$def = 'DECIMAL('.$settings['maxLength'].')';
					break;
			}
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
				$def .= ' DEFAULT '.$settings['default'];
		}

		if (isset($settings['unique']) && $settings['unique'] === true)
			$def .= ' UNIQUE';

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
							 TRIGGER `'.$dbName.'`.`auditinfoinsert_'.$tableName.'`
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
							 TRIGGER `'.$dbName.'`.`auditinfoupdate_'.$tableName.'`
							 BEFORE UPDATE ON `'.$dbName.'`.`{{'.$tableName.'}}`
							 FOR EACH ROW
							 SET NEW.date_updated = UNIX_TIMESTAMP(),
								 NEW.date_created = OLD.date_created;
							 END;
							 SQL;'
					)->execute();
	}
}

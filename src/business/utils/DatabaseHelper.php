<?php
namespace Blocks;

/**
 *
 */
class DatabaseHelper
{
	/**
	 * Default attribute settings
	 */
	protected static $attributeTypeDefaults = array(
		AttributeType::Char         => array('maxLength' => 255),
		AttributeType::Varchar      => array('maxLength' => 255),
		AttributeType::TinyInt      => array('maxLength' => 4),
		AttributeType::SmallInt     => array('maxLength' => 6),
		AttributeType::MediumInt    => array('maxLength' => 9),
		AttributeType::Int          => array('maxLength' => 11),
		AttributeType::BigInt       => array('maxLength' => 20),
		AttributeType::TinyInt      => array('maxLength' => 4),
		AttributeType::Decimal      => array('maxLength' => 10),
		AttributeType::Boolean      => array('type '=> AttributeType::TinyInt, 'maxLength' => 1, 'unsigned' => true, 'required' => true, 'default' => false),
		AttributeType::Enum         => array('values' => array()),

		// Common model attribute types
		AttributeType::ClassName    => array('type' => AttributeType::Char, 'maxLength' => 150, 'required' => true),
		AttributeType::Email        => array('type' => AttributeType::Varchar, 'minLength' => 5),
		AttributeType::Handle       => array('type' => AttributeType::Char, 'maxLength' => 100, 'required' => true),
		AttributeType::LanguageCode => array('type' => AttributeType::Char, 'maxLength' => 5, 'required' => true),
		AttributeType::Name         => array('type' => AttributeType::Varchar, 'maxLength' => 100, 'required' => true),
		AttributeType::SortOrder    => array('type' => AttributeType::SmallInt, 'required' => true, 'unsigned' => true),
		AttributeType::Template     => array('type' => AttributeType::Varchar, 'maxLength' => 500),
		AttributeType::Version      => array('type' => AttributeType::Char, 'maxLength' => 15, 'required' => true),
		AttributeType::Url          => array('type' => AttributeType::Varchar, 'maxLength' => 255),
		AttributeType::Build        => array('type' => AttributeType::Int, 'required' => true, 'unsigned' => true),
		AttributeType::Edition      => array('type' => AttributeType::Enum, 'values' => array('Personal', 'Standard', 'Pro'), 'required' => true),
		AttributeType::Key          => array('type' => AttributeType::Char, 'length' => 36, 'matchPattern' => '/[\w0-9]{8}-[\w0-9]{4}-[\w0-9]{4}-[\w0-9]{4}-[\w0-9]{12}/', 'required' => true, 'unique' => true),
	);

	/**
	 * Normalize attribute settings
	 *
	 * @param $settings
	 * @return array
	 */
	public static function normalizeAttributeSettings($settings)
	{
		if (is_string($settings))
			$settings = array('type' => $settings);
		else if (!isset($settings['type']))
			$settings['type'] = AttributeType::Varchar;

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

		// Treat strict lengths as max lengths when defining columns
		if (isset($settings['length']) && is_numeric($settings['length']) && $settings['length'] > 0)
			$settings['maxLength'] = $settings['length'];

		// Start the column definition
		switch ($settings['type'])
		{
			case AttributeType::Char:
				$def = 'CHAR('.$settings['maxLength'].')';
				break;
			case AttributeType::Varchar:
				$def = 'VARCHAR('.$settings['maxLength'].')';
				break;
			case AttributeType::TinyInt:
				$def = 'TINYINT('.$settings['maxLength'].')';
				break;
			case AttributeType::SmallInt:
				$def = 'SMALLINT('.$settings['maxLength'].')';
				break;
			case AttributeType::MediumInt:
				$def = 'MEDIUMINT('.$settings['maxLength'].')';
				break;
			case AttributeType::Int:
				$def = 'INT('.$settings['maxLength'].')';
				break;
			case AttributeType::BigInt:
				$def = 'BIGINT('.$settings['maxLength'].')';
				break;
			case AttributeType::Decimal:
				$def = 'DECIMAL('.$settings['maxLength'].')';
				break;
			case AttributeType::Enum:
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
		$dbName = Blocks::app()->config->getDbItem('database');

		Blocks::app()->db->createCommand(
							'CREATE
							 TRIGGER `'.$dbName.'`.`'.Blocks::app()->config->getDbItem('tablePrefix').'_auditinfoinsert_'.$tableName.'`
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
		$dbName = Blocks::app()->config->getDbItem('database');

		Blocks::app()->db->createCommand(
							'CREATE
							 TRIGGER `'.$dbName.'`.`'.Blocks::app()->config->getDbItem('tablePrefix').'_auditinfoupdate_'.$tableName.'`
							 BEFORE UPDATE ON `'.$dbName.'`.`{{'.$tableName.'}}`
							 FOR EACH ROW
							 SET NEW.date_updated = UNIX_TIMESTAMP(),
								 NEW.date_created = OLD.date_created;
							 END;
							 SQL;'
					)->execute();
	}
}

<?php
namespace Craft;

/**
 *
 */
class InfoModel extends BaseModel
{
	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'id'            => AttributeType::Number,
			'version'       => array(AttributeType::String, 'required' => true, 'default' => '0'),
			'build'         => array(AttributeType::Number, 'required' => true, 'default' => '0'),
			'schemaVersion' => array(AttributeType::String, 'required' => true, 'default' => '0'),
			'packages'      => array(AttributeType::Mixed, 'default' => array()),
			'releaseDate'   => array(AttributeType::DateTime, 'required' => true),
			'siteName'      => array(AttributeType::Name, 'required' => true),
			'siteUrl'       => array(AttributeType::Url, 'required' => true),
			'timezone'      => array(AttributeType::String, 'maxLength' => 30, 'default' => date_default_timezone_get()),
			'on'            => AttributeType::Bool,
			'maintenance'   => AttributeType::Bool,
			'track'         => array(AttributeType::String, 'maxLength' => 40, 'column' => ColumnType::Varchar, 'required' => true),
			'uid'           => AttributeType::String,
		);
	}

	/**
	 * Sets an attribute's value.
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return bool
	 */
	public function setAttribute($name, $value)
	{
		// Set packages as an array
		if ($name == 'packages' && !is_array($value))
		{
			if ($value)
			{
				$value = array_filter(ArrayHelper::stringToArray($value));
				sort($value);
			}
			else
			{
				$value = array();
			}
		}

		return parent::setAttribute($name, $value);
	}

	/**
	 * Gets an attribute's value.
	 *
	 * @param string $name
	 * @param bool $flattenValue
	 * @return mixed
	 */
	public function getAttribute($name, $flattenValue = false)
	{
		// Flatten packages into a comma-delimited string, rather than JSON
		if ($name == 'packages' && $flattenValue)
		{
			return implode(',', parent::getAttribute('packages'));
		}
		else
		{
			return parent::getAttribute($name, $flattenValue);
		}
	}
}

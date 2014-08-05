<?php
namespace Craft;

/**
 * Class LightswitchFieldType
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.fieldtypes
 * @since     1.3
 */
class LightswitchFieldType extends BaseFieldType
{
	////////////////////
	// PUBLIC METHODS
	////////////////////

	/**
	 * Returns the type of field this is.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Lightswitch');
	}

	/**
	 * Returns the content attribute config.
	 *
	 * @return mixed
	 */
	public function defineContentAttribute()
	{
		return AttributeType::Bool;
	}

	/**
	 * Returns the field's input HTML.
	 *
	 * @param string $name
	 * @param mixed  $value
	 *
	 * @return string
	 */
	public function getInputHtml($name, $value)
	{
		return craft()->templates->render('_includes/forms/lightswitch', array(
			'name'  => $name,
			'on'    => (bool) $value,
		));
	}

	/**
	 * Returns the input value as it should be saved to the database.
	 *
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	public function prepValueFromPost($value)
	{
		return (bool) $value;
	}

	/**
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	public function prepValue($value)
	{
		// It's stored as '0' in the database, but it's returned as false. Change it back to '0'.
		return $value == false ? '0' : $value;
	}
}

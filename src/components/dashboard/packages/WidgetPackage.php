<?php
namespace Blocks;

/**
 * Widget package class
 *
 * Used for transporting widget data throughout the system.
 */
class WidgetPackage
{
	public $id;
	public $class;
	public $settings;

	public $errors;
	public $settingsErrors;

	/**
	 * Saves the Widget.
	 *
	 * @return bool
	 */
	public function save()
	{
		return blx()->dashboard->saveUserWidget($this);
	}
}

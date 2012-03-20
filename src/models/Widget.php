<?php
namespace Blocks;

/**
 *
 */
class Widget extends Model
{
	// Model properties

	protected $tableName = 'widgets';
	protected $settingsTableName = 'widgetsettings';
	protected $foreignKeyName = 'widget_id';
	protected $classSuffix = 'Widget';
	protected $hasSettings = true;

	protected $attributes = array(
		'class'      => AttributeType::ClassName,
		'sort_order' => AttributeType::SortOrder
	);

	protected $belongsTo = array(
		'user'   => array('model' => 'User', 'required' => true),
		//'plugin' => array('model' => 'Plugin')
	);

	// Widget subclass properties

	public $widgetName;
	public $title = '';

	protected $bodyTemplate;
	protected $settingsTemplate;

	/**
	 * Display the widget's body
	 * @return bool
	 */
	public function displayBody()
	{
		if (empty($this->bodyTemplate))
			return '';

		$tags = array(
			'widget' => $this
		);

		$template = b()->controller->loadTemplate($this->bodyTemplate, $tags, true);
		return $template;
	}

	/**
	 * Display the settings form
	 * @return bool
	 */
	public function displaySettings()
	{
		if (empty($this->settingsTemplate))
			return '';

		$tags = array(
			'settings' => $this->settings
		);

		$template = b()->controller->loadTemplate($this->settingsTemplate, $tags, true);
		return $template;
	}
}

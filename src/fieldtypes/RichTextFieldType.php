<?php
namespace Craft;

/**
 *
 */
class RichTextFieldType extends BaseFieldType
{
	/**
	 * Returns the type of field this is.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Rich Text');
	}

	/**
	 * Defines the settings.
	 *
	 * @access protected
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'minHeight' => array(AttributeType::Number, 'default' => 100, 'min' => 1),
		);
	}

	/**
	 * Returns the field's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		return craft()->templates->renderMacro('_includes/forms', 'textField', array(
			array(
				'label'  => Craft::t('Min Height (in pixels)'),
				'id'     => 'minHeight',
				'name'   => 'minHeight',
				'value'  => $this->getSettings()->minHeight,
				'size'   => 3,
				'errors' => $this->getSettings()->getErrors('minHeight')
			)
		));
	}

	/**
	 * Returns the content attribute config.
	 *
	 * @return mixed
	 */
	public function defineContentAttribute()
	{
		return array(AttributeType::String, 'column' => ColumnType::Text);
	}

	/**
	 * Preps the field value for use.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	public function prepValue($value)
	{
		if ($value)
		{
			// Prevent everyone from having to use the |raw filter when outputting RTE content
			$charset = craft()->templates->getTwig()->getCharset();
			return new RichTextData($value, $charset);
		}
		else
		{
			return null;
		}
	}

	/**
	 * Returns the field's input HTML.
	 *
	 * @param string $name
	 * @param mixed  $value
	 * @return string
	 */
	public function getInputHtml($name, $value)
	{
		craft()->templates->includeCssResource('lib/redactor/redactor.css');
		craft()->templates->includeCssResource('lib/redactor/plugins/pagebreak.css');
		craft()->templates->includeJsResource('lib/redactor/redactor'.(craft()->config->get('useCompressedJs') ? '.min' : '').'.js');
		craft()->templates->includeJsResource('lib/redactor/plugins/fullscreen.js');
		craft()->templates->includeJsResource('lib/redactor/plugins/pagebreak.js');

		$config = array(
			'buttons' => array('html','|','formatting','|','bold','italic','|','unorderedlist','orderedlist','|','link','image','video','table'),
			'plugins' => array('fullscreen', 'pagebreak'),
		);

		if ($this->getSettings()->minHeight)
		{
			$config['minHeight'] = $this->getSettings()->minHeight;
		}

		$configJson = JsonHelper::encode($config);

		craft()->templates->includeJs('$(".redactor-'.$this->model->handle.'").redactor('.$configJson.');');

		// Swap any <!--pagebreak-->'s with <hr>'s
		$value = str_replace('<!--pagebreak-->', '<hr class="redactor_pagebreak" unselectable="on" contenteditable="false" />', $value);

		return '<textarea name="'.$name.'" class="redactor-'.$this->model->handle.'" style="display: none">'.$value.'</textarea>';
	}

	/**
	 * Preps the post data before it's saved to the database.
	 *
	 * @access protected
	 * @param mixed $value
	 * @return mixed
	 */
	protected function prepPostData($value)
	{
		if ($value)
		{
			// Swap any pagebreak <hr>'s with <!--pagebreak-->'s
			$value = preg_replace('/<hr class="redactor_pagebreak" unselectable="on" contenteditable="false"\s*(\/)?>/', '<!--pagebreak-->', $value);
		}

		return $value;
	}
}

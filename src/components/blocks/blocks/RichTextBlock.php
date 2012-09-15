<?php
namespace Blocks;

/**
 *
 */
class RichTextBlock extends BaseBlock
{
	/**
	 * Returns the type of block this is.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Blocks::t('Rich Text');
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
	 * Returns the block's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		return blx()->templates->render('_components/blocks/RichText/settings', array(
			'settings' => $this->getSettings()
		));
	}

	/**
	 * Returns the content attribute config.
	 *
	 * @return string|array
	 */
	public function defineContentAttribute()
	{
		return array(AttributeType::String, 'column' => ColumnType::Text);
	}

	/**
	 * Returns the block's input HTML.
	 *
	 * @param string     $handle
	 * @param mixed      $value
	 * @param array|null $errors
	 * @return string
	 */
	public function getInputHtml($handle, $value, $errors = null)
	{
		blx()->templates->includeCssFile(UrlHelper::getResourceUrl('lib/redactor/redactor.css'));
		blx()->templates->includeJsFile(UrlHelper::getResourceUrl('lib/redactor/redactor.min.js'));

		if ($this->getSettings()->minHeight)
		{
			$config['minHeight'] = $this->getSettings()->minHeight;
		}

		$configJson = !empty($config) ? JsonHelper::encode($config) : null;

		blx()->templates->includeJs('$(".redactor").redactor('.$configJson.');');

		return '<textarea id="'.$handle.'" name="'.$handle.'" class="redactor">'.$value.'</textarea>';
	}
}

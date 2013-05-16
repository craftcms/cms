<?php
namespace Craft;

/**
 * Clear Caches tool
 */
class ClearCachesTool extends BaseTool
{
	/**
	 * Returns the tool name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Clear Caches');
	}

	/**
	 * Returns the tool's options HTML.
	 *
	 * @return string
	 */
	public function getOptionsHtml()
	{
		$options = array(
			array('label' => Craft::t('File caches'), 'value' => 'cache'),
			array('label' => Craft::t('Asset thumbs'), 'value' => 'assets'),
			array('label' => Craft::t('Compiled templates'), 'value' => 'compiled_templates'),
			array('label' => Craft::t('Temp files'), 'value' => 'temp'),
		);

		return craft()->templates->render('_includes/forms/checkboxSelect', array(
			'name'    => 'caches',
			'options' => $options
		));
	}
}

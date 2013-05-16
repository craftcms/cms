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
	 * Returns the tool's icon value.
	 *
	 * @return string
	 */
	public function getIconValue()
	{
		return 'trash';
	}

	/**
	 * Returns the tool's options HTML.
	 *
	 * @return string
	 */
	public function getOptionsHtml()
	{
		return craft()->templates->render('_includes/forms/checkboxSelect', array(
			'name'    => 'folders',
			'options' => $this->_getFolders()
		));
	}

	/**
	 * Returns the tool's button label.
	 *
	 * @return string
	 */
	public function getButtonLabel()
	{
		return Craft::t('Clear!');
	}

	/**
	 * Performs the tool's action.
	 *
	 * @param array $params
	 * @return array
	 */
	public function performAction($params = array())
	{
		if (!isset($params['folders']))
		{
			return;
		}

		$allFolders = array_keys($this->_getFolders());

		if ($params['folders'] == '*')
		{
			$folders = $allFolders;
		}
		else
		{
			$folders = $params['folders'];
		}

		foreach ($folders as $folder)
		{
			if (in_array($folder, $allFolders))
			{
				$path = craft()->path->getRuntimePath().$folder;
				IOHelper::clearFolder($path, true);
			}
		}
	}

	/**
	 * Returns the cache folders we allow to be cleared.
	 *
	 * @access private
	 * @return array
	 */
	private function _getFolders()
	{
		return array(
			'cache' => Craft::t('File caches'),
			'assets' => Craft::t('Asset thumbs'),
			'compiled_templates' => Craft::t('Compiled templates'),
			'temp' => Craft::t('Temp files'),
		);
	}
}

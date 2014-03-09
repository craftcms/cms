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
		$caches = $this->_getFolders();
		$caches['templateCaches'] = Craft::t('Template Caches');

		return craft()->templates->render('_includes/forms/checkboxSelect', array(
			'name'    => 'caches',
			'options' => $caches
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
	 * @return void
	 */
	public function performAction($params = array())
	{
		if (!isset($params['caches']))
		{
			return;
		}

		$allFolderKeys = array_keys($this->_getFolders());

		if ($params['caches'] == '*')
		{
			$folders = $allFolderKeys;
		}
		else
		{
			$folders = array();

			foreach ($params['caches'] as $cacheKey)
			{
				if (in_array($cacheKey, $allFolderKeys))
				{
					$folders[] = $cacheKey;
				}
			}
		}

		$allFolders = array_keys($this->_getFolders(false));

		foreach ($folders as $folder)
		{
			foreach ($allFolders as $allFolder)
			{
				if (md5($allFolder) == $folder)
				{
					if ($allFolder == 'dataCache')
					{
						craft()->cache->flush();
					}
					else
					{
						IOHelper::clearFolder($allFolder, true);
						break;
					}
				}
			}
		}

		if ($params['caches'] == '*' || in_array('templateCaches', $params['caches']))
		{
			craft()->templateCache->deleteAllCaches();
		}
	}

	/**
	 * Returns the cache folders we allow to be cleared as well as any plugin cache paths that have used the 'registerCachePaths' hook.
	 *
	 * @access private
	 * @param  bool    $obfuscate If true, will MD5 the path so it will be obfuscated in the template.
	 * @return array
	 */
	private function _getFolders($obfuscate = true)
	{
		$runtimePath = craft()->path->getRuntimePath();

		$folders = array(
			$obfuscate ? md5('dataCache') : 'dataCache'                                             => Craft::t('Data cache'),
			$obfuscate ? md5($runtimePath.'cache') : $runtimePath.'cache'                           => Craft::t('RSS cache'),
			$obfuscate ? md5($runtimePath.'assets') : $runtimePath.'assets'                         => Craft::t('Asset thumbs'),
			$obfuscate ? md5($runtimePath.'compiled_templates') : $runtimePath.'compiled_templates' => Craft::t('Compiled templates'),
			$obfuscate ? md5($runtimePath.'temp') : $runtimePath.'temp'                             => Craft::t('Temp files'),
		);

		$pluginCachePaths = craft()->plugins->call('registerCachePaths');

		if (is_array($pluginCachePaths) && count($pluginCachePaths) > 0)
		{
			foreach ($pluginCachePaths as $paths)
			{
				foreach ($paths as $path => $label)
				{
					$folders[$obfuscate ? md5($path) : $path] = $label;
				}
			}
		}

		return $folders;
	}
}

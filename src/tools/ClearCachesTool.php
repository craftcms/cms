<?php
namespace Craft;

/**
 * Clear Caches tool.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.tools
 * @since     1.0
 */
class ClearCachesTool extends BaseTool
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc IComponentType::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Clear Caches');
	}

	/**
	 * @inheritDoc ITool::getIconValue()
	 *
	 * @return string
	 */
	public function getIconValue()
	{
		return 'trash';
	}

	/**
	 * @inheritDoc ITool::getOptionsHtml()
	 *
	 * @return string
	 */
	public function getOptionsHtml()
	{
		$caches = $this->_getFolders();
		$caches['assetTransformIndex'] = Craft::t('Asset transform index');
		$caches['assetIndexingData'] = Craft::t('Asset indexing data');
		$caches['templateCaches'] = Craft::t('Template caches');

		return craft()->templates->render('_includes/forms/checkboxSelect', array(
			'name'    => 'caches',
			'options' => $caches
		));
	}

	/**
	 * @inheritDoc ITool::getButtonLabel()
	 *
	 * @return string
	 */
	public function getButtonLabel()
	{
		return Craft::t('Clear!');
	}

	/**
	 * @inheritDoc ITool::performAction()
	 *
	 * @param array $params
	 *
	 * @return null
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

		if ($params['caches'] == '*' || in_array('assetTransformIndex', $params['caches']))
		{
			craft()->db->createCommand()->truncateTable('assettransformindex');
		}

		if ($params['caches'] == '*' || in_array('assetIndexingData', $params['caches']))
		{
			craft()->db->createCommand()->truncateTable('assetindexdata');
		}
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns the cache folders we allow to be cleared as well as any plugin cache paths that have used the
	 * 'registerCachePaths' hook.
	 *
	 * @param bool $obfuscate If true, will MD5 the path so it will be obfuscated in the template.
	 *
	 * @return array
	 */
	private function _getFolders($obfuscate = true)
	{
		$runtimePath = craft()->path->getRuntimePath();

		$folders = array(
			$obfuscate ? md5('dataCache') : 'dataCache'                                             => Craft::t('Data caches'),
			$obfuscate ? md5($runtimePath.'cache') : $runtimePath.'cache'                           => Craft::t('RSS caches'),
			$obfuscate ? md5($runtimePath.'assets') : $runtimePath.'assets'                         => Craft::t('Asset caches'),
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

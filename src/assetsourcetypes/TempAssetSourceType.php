<?php
namespace Craft;

/**
 * Temp source type class
 */
class TempAssetSourceType extends LocalAssetSourceType
{

	protected $_isSourceLocal = true;

	/**
	 * Returns the name of the source type.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Temp Folder');
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
			'path' => array(AttributeType::String, 'required' => true),
			'url'  => array(AttributeType::String, 'required' => true, 'label' => 'URL'),
		);
	}

	/**
	 * Returns the component's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		return null;
	}

	/**
	 * Preps the settings before they're saved to the database.
	 *
	 * @param array $settings
	 * @return array
	 */
	public function prepSettings($settings)
	{
		// Add a trailing slash to the Path and URL settings
		$settings['path'] = !empty($settings['path']) ? rtrim($settings['path'], '/').'/' : '';
		$settings['url'] = !empty($settings['url']) ? rtrim($settings['url'], '/').'/' : '';

		return $settings;
	}

	/**
	 * Starts an indexing session.
	 *
	 * @param $sessionId
	 * @return array
	 * @throws Exception
	 */
	public function startIndex($sessionId)
	{
		throw new Exception(Craft::t("This Source Type does not support indexing."));
	}

	/**
	 * Get the file system path for upload source.
	 *
	 * @param BaseAssetSourceType|LocalAssetSourceType $sourceType = null
	 * @return string
	 */
	private function _getSourceFileSystemPath(LocalAssetSourceType $sourceType = null)
	{
		$path = is_null($sourceType) ? $this->getBasePath() : $sourceType->getBasePath();
		$path = IOHelper::getRealPath($path);
		return $path;
	}

	/**
	 * Process an indexing session.
	 *
	 * @param $sessionId
	 * @param $offset
	 * @return mixed
	 * @throws Exception
	 */
	public function processIndex($sessionId, $offset)
	{
		throw new Exception(Craft::t("This Source Type does not support indexing."));
	}

	/**
	 * Cannot be selected. Ever.
	 *
	 * @return bool
	 */
	public function isSelectable()
	{
		return false;
	}
}

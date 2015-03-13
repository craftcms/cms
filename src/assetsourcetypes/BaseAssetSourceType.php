<?php
/**
 * The base class for all asset source types.  Any asset source type must extend this class.
 *
 * @author     Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright  Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license    http://buildwithcraft.com/license Craft License Agreement
 * @see        http://buildwithcraft.com
 * @package    craft.app.assetsourcetypes
 * @since      1.0
 */

namespace craft\app\assetsourcetypes;

use Craft;
use craft\app\dates\DateTime;
use craft\app\errors\Exception;
use craft\app\filesourcetypes\BaseFlysystemFileSourceType;
use craft\app\helpers\IOHelper;
use craft\app\models\Asset as AssetModel;

abstract class BaseAssetSourceType extends BaseFlysystemFileSourceType implements IAssetSourceType
{
	// Properties
	// =========================================================================

	/**
	 * Whether this is a local source or not. Defaults to false.
	 *
	 * @var bool
	 */
	protected $isSourceLocal = false;

	/**
	 * The type of component, e.g. 'Plugin', 'Widget', 'FieldType', etc. Defined by the component type's base class.
	 *
	 * @var string
	 */
	protected $componentType = 'AssetSourceType';

	// Public Methods
	// =========================================================================

	/**
	 * Returns whether this source stores files locally on the server.
	 *
	 * @return bool Whether files are stored locally.
	 */
	public function isLocal()
	{
		return $this->isSourceLocal;
	}

	/**
	 * Returns any type-specific validation errors
	 *
	 * @return array
	 */
	public function getSourceErrors()
	{
		$errors = array();

		return $errors;
	}

	/**
	 * Save a file from the source's uriPath to a target path.
	 *
	 * @param $uriPath
	 * @param $targetPath
	 *
	 * @return int $bytes amount of bytes copied
	 */
	public function saveFile($uriPath, $targetPath)
	{
		$stream = $this->getFilesystem()->readStream($uriPath);
		$outputStream = fopen($targetPath, 'wb');

		rewind($stream);
		$bytes = stream_copy_to_stream($stream, $outputStream);

		fclose($stream);
		fclose($outputStream);

		return $bytes;
	}

	/**
	 * Load Source Type Data.
	 *
	 * @param $dataType
	 * @param $parameters
	 *
	 * @throws Exception
	 * @return mixed
	 */

	public static final function loadSourceTypeData($dataType, $parameters)
	{
		if (!method_exists(get_called_class(), 'load'.ucfirst($dataType)))
		{
			throw new Exception (Craft::t('app', "Don't know how to load “".$dataType.'”!'));
		}

		return call_user_func_array(array(get_called_class(), 'load'.ucfirst($dataType)), $parameters);
	}

	/**
	 * Return a path where the image sources are being stored for this source.
	 *
	 * @return string
	 */
	public function getImageTransformSourceLocation()
	{
		return Craft::$app->path->getAssetsImageSourcePath();
	}

	/**
	 * @inheritDoc IAssetSourceType::getRootUrl()
	 *
	 * @return string
	 */
	public function getRootUrl()
	{
		if (is_object($this->model))
		{
			$settings = $this->getSettings();

			$appendix = '';
			
			if (!empty($settings->subfolder))
			{
				$appendix = rtrim($settings->subfolder, '/').'/';
			}

			return rtrim($this->model->url, '/').'/'.$appendix;
		}

		return '';
	}
}

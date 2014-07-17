<?php
namespace Craft;

/**
 * Interface IZip
 *
 * @package craft.app.etc.io
 */
interface IZip
{
	/**
	 * @param $sourceFolder
	 * @param $destZip
	 * @return mixed
	 */
	function zip($sourceFolder, $destZip);

	/**
	 * @param $sourceZip
	 * @param $destFolder
	 * @return mixed
	 */
	function unzip($sourceZip, $destFolder);

	/**
	 * @param      $sourceZip
	 * @param      $filePath
	 * @param      $basePath
	 * @param null $pathPrefix
	 * @return mixed
	 */
	function add($sourceZip, $filePath, $basePath, $pathPrefix = null);
}

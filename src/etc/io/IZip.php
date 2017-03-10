<?php
namespace Craft;

/**
 * Interface IZip
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.io
 * @since     1.0
 */
interface IZip
{
	// Public Methods
	// =========================================================================

	/**
	 * @param $sourceFolder
	 * @param $destZip
	 *
	 * @return mixed
	 */
	public function zip($sourceFolder, $destZip);

	/**
	 * @param $sourceZip
	 * @param $destFolder
	 *
	 * @return mixed
	 */
	public function unzip($sourceZip, $destFolder);

	/**
	 * Will add either a file or a folder to an existing zip file.  If it is a folder, it will add the contents
	 * recursively.
	 *
	 * @param string $sourceZip  The zip file to be added to.
	 * @param string $filePath   A file or a folder to add.  If it is a folder, it will recursively add the contents of
	 *                           the folder to the zip.
	 * @param string $basePath   The root path of the file(s) to be added that will be removed before adding.
	 * @param string $pathPrefix A path to be prepended to each file before it is added to the zip.
	 *
	 * @return bool
	 */
	public function add($sourceZip, $filePath, $basePath, $pathPrefix = null);
}

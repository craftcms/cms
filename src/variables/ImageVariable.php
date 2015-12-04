<?php
namespace Craft;

/**
 * Class ImageVariable
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.variables
 * @since     1.0
 */
class ImageVariable
{
	// Properties
	// =========================================================================

	/**
	 * @var string
	 */
	protected $path;

	/**
	 * @var string
	 */
	protected $url;

	/**
	 * @var
	 */
	protected $size;

	// Public Methods
	// =========================================================================

	/**
	 * Constructor
	 *
	 * @param string $path
	 *
	 * @return ImageVariable
	 */
	public function __construct($path, $url = "")
	{
		$this->path = $path;
		$this->url = $url;
	}

	/**
	 * Returns an array of the width and height of the image.
	 *
	 * @return array
	 */
	public function getSize()
	{
		if (!isset($this->size))
		{
			$size = ImageHelper::getImageSize($this->path);
			$this->size = array($size[0], $size[1]);
		}

		return $this->size;
	}

	/**
	 * Returns the image's width.
	 *
	 * @return int
	 */
	public function getWidth()
	{
		$size = $this->getSize();
		return $size[0];
	}

	/**
	 * Returns the image's height.
	 *
	 * @return int
	 */
	public function getHeight()
	{
		$size = $this->getSize();
		return $size[1];
	}

	/**
	 * Returns the image's URL.
	 *
	 * @return string
	 */
	public function getUrl()
	{
		return $this->url;
	}
}

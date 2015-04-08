<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web\twig\variables;

/**
 * Class Image variable.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Image
{
	// Properties
	// =========================================================================

	/**
	 * @var string
	 */
	protected $path;

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
	 * @return Image
	 */
	public function __construct($path)
	{
		$this->path = $path;
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
			$size = getimagesize($this->path);
			$this->size = [$size[0], $size[1]];
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
}

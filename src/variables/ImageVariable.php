<?php
namespace Craft;

/**
 * Class ImageVariable
 *
 * @package craft.app.validators
 */
class ImageVariable
{
	protected $path;
	protected $size;

	/**
	 * Constructor
	 *
	 * @param string $path
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
}

<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\base;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Filesystem;

/**
 * VolumeTrait
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
trait VolumeTrait
{
	// Properties
	// =========================================================================

	/**
	 * @var string Name
	 */
	public $name;

	/**
	 * @var string Handle
	 */
	public $handle;

	/**
	 * @var string The source’s URL
	 */
	public $url;

	/**
	 * @var integer Sort order
	 */
	public $sortOrder;

	/**
	 * @var integer Field layout ID
	 */
	public $fieldLayoutId;

	/**
	 * Set to true if the Adapter expects folder names to have trailing slashes
	 *
	 * @var bool
	 */
	protected $foldersHaveTrailingSlashes = true;

	/**
	 * The Flysystem adapter, created by {@link createAdapter()}.
	 *
	 * @var AdapterInterface
	 */
	private $_adapter;

	/**
	 * The Flysystem filesystem.
	 *
	 * @var Filesystem
	 */
	private $_filesystem;

	/**
	 * @var string The element type that global sets' field layouts should be associated with.
	 */
	private $_fieldLayoutElementType = 'craft\app\elements\Asset';

}

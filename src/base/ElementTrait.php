<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\base;

/**
 * ElementTrait implements the common methods and properties for element classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
trait ElementTrait
{
	// Properties
	// =========================================================================

	/**
	 * @var int The element’s ID
	 */
	public $id;

	/**
	 * @var boolean Whether the element is enabled
	 */
	public $enabled = true;

	/**
	 * @var boolean Whether the element is archived
	 */
	public $archived = false;

	/**
	 * @var string The element’s locale
	 */
	public $locale;

	/**
	 * @var boolean Whether the element is enabled for this [[locale]].
	 */
	public $localeEnabled = true;

	/**
	 * @var string The element’s slug
	 */
	public $slug;

	/**
	 * @var string The element’s URI
	 */
	public $uri;

	/**
	 * @var DateTime The date that the element was created
	 */
	public $dateCreated;

	/**
	 * @var DateTime The date that the element was last updated
	 */
	public $dateUpdated;

	/**
	 * @var int The element’s structure’s root ID
	 */
	public $root;

	/**
	 * @var int The element’s left position within its structure
	 */
	public $lft;

	/**
	 * @var int The element’s right position within its structure
	 */
	public $rgt;

	/**
	 * @var int The element’s level within its structure
	 */
	public $level;
}

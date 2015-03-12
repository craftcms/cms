<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\fieldtypes;

use Craft;
use craft\app\elements\Entry;

/**
 * Entries fieldtype
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Entries extends BaseElementFieldType
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function getName()
	{
		return Craft::t('app', 'Entries');
	}

	/**
	 * @inheritdoc
	 * @return Entry
	 */
	public function getElementClass()
	{
		return Entry::className();
	}

	/**
	 * @inheritdoc
	 */
	public function getAddButtonLabel()
	{
		return Craft::t('app', 'Add an entry');
	}
}

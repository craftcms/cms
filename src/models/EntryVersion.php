<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\enums\AttributeType;
use craft\app\models\EntryVersion as EntryVersionModel;

Craft::$app->requireEdition(Craft::Client);

/**
 * Class EntryVersion model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class EntryVersion extends BaseEntryRevisionModel
{
	// Properties
	// =========================================================================

	/**
	 * @var integer ID
	 */
	public $id;

	/**
	 * @var boolean Enabled
	 */
	public $enabled = true;

	/**
	 * @var boolean Archived
	 */
	public $archived = false;

	/**
	 * @var string Locale
	 */
	public $locale = 'en-US';

	/**
	 * @var boolean Locale enabled
	 */
	public $localeEnabled = true;

	/**
	 * @var string Slug
	 */
	public $slug;

	/**
	 * @var string URI
	 */
	public $uri;

	/**
	 * @var \DateTime Date created
	 */
	public $dateCreated;

	/**
	 * @var \DateTime Date updated
	 */
	public $dateUpdated;

	/**
	 * @var integer Root
	 */
	public $root;

	/**
	 * @var integer Lft
	 */
	public $lft;

	/**
	 * @var integer Rgt
	 */
	public $rgt;

	/**
	 * @var integer Level
	 */
	public $level;

	/**
	 * @var integer Section ID
	 */
	public $sectionId;

	/**
	 * @var integer Type ID
	 */
	public $typeId;

	/**
	 * @var integer Author ID
	 */
	public $authorId;

	/**
	 * @var \DateTime Post date
	 */
	public $postDate;

	/**
	 * @var \DateTime Expiry date
	 */
	public $expiryDate;

	/**
	 * @var integer New parent ID
	 */
	public $newParentId;

	/**
	 * @var string Revision notes
	 */
	public $revisionNotes;

	/**
	 * @var integer Creator ID
	 */
	public $creatorId;

	/**
	 * @var integer Version ID
	 */
	public $versionId;

	/**
	 * @var integer Num
	 */
	public $num;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'slug' => Craft::t('app', 'Slug'),
			'uri' => Craft::t('app', 'URI'),
		];
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['id'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['locale'], 'craft\\app\\validators\\Locale'],
			[['dateCreated'], 'craft\\app\\validators\\DateTime'],
			[['dateUpdated'], 'craft\\app\\validators\\DateTime'],
			[['root'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['lft'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['rgt'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['level'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['sectionId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['typeId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['authorId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['postDate'], 'craft\\app\\validators\\DateTime'],
			[['expiryDate'], 'craft\\app\\validators\\DateTime'],
			[['newParentId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['creatorId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['versionId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['num'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['id', 'enabled', 'archived', 'locale', 'localeEnabled', 'slug', 'uri', 'dateCreated', 'dateUpdated', 'root', 'lft', 'rgt', 'level', 'sectionId', 'typeId', 'authorId', 'postDate', 'expiryDate', 'newParentId', 'revisionNotes', 'creatorId', 'versionId', 'num'], 'safe', 'on' => 'search'],
		];
	}

	/**
	 * @inheritDoc Model::populateModel()
	 *
	 * @param mixed $attributes
	 *
	 * @return EntryVersionModel
	 */
	public static function populateModel($attributes)
	{
		if ($attributes instanceof \yii\base\Model)
		{
			$attributes = $attributes->getAttributes();
		}

		// Merge the version and entry data
		$entryData = $attributes['data'];
		$fieldContent = isset($entryData['fields']) ? $entryData['fields'] : null;
		$attributes['versionId'] = $attributes['id'];
		$attributes['id'] = $attributes['entryId'];
		$attributes['revisionNotes'] = $attributes['notes'];
		$title = $entryData['title'];
		unset($attributes['data'], $entryData['fields'], $attributes['entryId'], $attributes['notes'], $entryData['title']);

		$attributes = array_merge($attributes, $entryData);

		// Initialize the version
		$version = parent::populateModel($attributes);
		$version->getContent()->title = $title;

		if ($fieldContent)
		{
			$version->setContentFromRevision($fieldContent);
		}

		return $version;
	}
}

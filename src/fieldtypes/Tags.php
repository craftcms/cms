<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\fieldtypes;

use Craft;
use craft\app\elements\db\ElementQueryInterface;
use craft\app\elements\Tag;
use craft\app\models\TagGroup as TagGroupModel;

/**
 * Tags fieldtype
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Tags extends BaseElementFieldType
{
	// Properties
	// =========================================================================

	/**
	 * Whether the field settings should allow multiple sources to be selected.
	 *
	 * @var bool $allowMultipleSources
	 */
	protected $allowMultipleSources = false;

	/**
	 * Whether to allow the Limit setting.
	 *
	 * @var bool $allowLimit
	 */
	protected $allowLimit = false;

	/**
	 * @var
	 */
	private $_tagGroupId;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function getName()
	{
		return Craft::t('app', 'Tags');
	}

	/**
	 * @inheritdoc
	 * @return Tag
	 */
	public function getElementClass()
	{
		return Tag::className();
	}

	/**
	 * @inheritdoc
	 */
	public function getAddButtonLabel()
	{
		return Craft::t('app', 'Add a tag');
	}

	/**
	 * @inheritDoc FieldTypeInterface::getInputHtml()
	 *
	 * @param string                     $name
	 * @param ElementQueryInterface|null $selectedElementsQuery
	 *
	 * @return string
	 */
	public function getInputHtml($name, $selectedElementsQuery)
	{
		if (!($selectedElementsQuery instanceof ElementQueryInterface))
		{
			$class = $this->getElementClass();
			$selectedElementsQuery = $class::find()
				->id(false);
		}

		$tagGroup = $this->_getTagGroup();

		if ($tagGroup)
		{
			return Craft::$app->templates->render('_components/fieldtypes/Tags/input', [
				'elementClass'    => $this->getElementClass(),
				'id'              => Craft::$app->templates->formatInputId($name),
				'name'            => $name,
				'elements'        => $selectedElementsQuery,
				'tagGroupId'      => $this->_getTagGroupId(),
				'sourceElementId' => (isset($this->element->id) ? $this->element->id : null),
			]);
		}
		else
		{
			return '<p class="error">'.Craft::t('app', 'This field is not set to a valid source.').'</p>';
		}
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns the tag group associated with this field.
	 *
	 * @return TagGroupModel|null
	 */
	private function _getTagGroup()
	{
		$tagGroupId = $this->_getTagGroupId();

		if ($tagGroupId)
		{
			return Craft::$app->tags->getTagGroupById($tagGroupId);
		}
	}

	/**
	 * Returns the tag group ID this field is associated with.
	 *
	 * @return int|false
	 */
	private function _getTagGroupId()
	{
		if (!isset($this->_tagGroupId))
		{
			$source = $this->getSettings()->source;

			if (strncmp($source, 'taggroup:', 9) == 0)
			{
				$this->_tagGroupId = (int) mb_substr($source, 9);
			}
			else
			{
				$this->_tagGroupId = false;
			}
		}

		return $this->_tagGroupId;
	}
}

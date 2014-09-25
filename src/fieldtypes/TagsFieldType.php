<?php
namespace Craft;

/**
 * Class TagsFieldType
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.fieldtypes
 * @since     1.2
 */
class TagsFieldType extends BaseElementFieldType
{
	// Properties
	// =========================================================================

	/**
	 * The element type this field deals with.
	 *
	 * @var string $elementType
	 */
	protected $elementType = 'Tag';

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
	 * @inheritDoc IFieldType::getInputHtml()
	 *
	 * @param string $name
	 * @param mixed  $criteria
	 *
	 * @return string
	 */
	public function getInputHtml($name, $criteria)
	{
		if (!($criteria instanceof ElementCriteriaModel))
		{
			$criteria = craft()->elements->getCriteria($this->elementType);
			$criteria->id = false;
		}

		$elementVariable = new ElementTypeVariable($this->getElementType());

		$tagGroup = $this->_getTagGroup();

		if ($tagGroup)
		{
			return craft()->templates->render('_components/fieldtypes/Tags/input', array(
				'elementType'     => $elementVariable,
				'id'              => craft()->templates->formatInputId($name),
				'name'            => $name,
				'elements'        => $criteria,
				'tagGroupId'      => $this->_getTagGroupId(),
				'sourceElementId' => (isset($this->element->id) ? $this->element->id : null),
			));
		}
		else
		{
			return '<p class="error">'.Craft::t('This field is not set to a valid source.').'</p>';
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
			return craft()->tags->getTagGroupById($tagGroupId);
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

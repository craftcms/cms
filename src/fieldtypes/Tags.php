<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\fieldtypes;

use Craft;
use craft\app\models\ElementCriteria as ElementCriteriaModel;
use craft\app\models\TagGroup as TagGroupModel;
use craft\app\variables\ElementType;

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
	 * @inheritDoc FieldTypeInterface::getInputHtml()
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
			$criteria = Craft::$app->elements->getCriteria($this->elementType);
			$criteria->id = false;
		}

		$elementVariable = new ElementType($this->getElementType());

		$tagGroup = $this->_getTagGroup();

		if ($tagGroup)
		{
			return Craft::$app->templates->render('_components/fieldtypes/Tags/input', [
				'elementType'     => $elementVariable,
				'id'              => Craft::$app->templates->formatInputId($name),
				'name'            => $name,
				'elements'        => $criteria,
				'tagGroupId'      => $this->_getTagGroupId(),
				'sourceElementId' => (isset($this->element->id) ? $this->element->id : null),
			]);
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

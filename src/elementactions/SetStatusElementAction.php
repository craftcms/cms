<?php
namespace Craft;

/**
 * Set Status Element Action
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.elementactions
 * @since     2.3
 */
class SetStatusElementAction extends BaseElementAction
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc ISavableComponentType::getTriggerHtml()
	 *
	 * @return string|null
	 */
	public function getTriggerHtml()
	{
		return craft()->templates->render('_components/elementactions/SetStatus/trigger');
	}

	/**
	 * @inheritDoc IElementAction::performAction()
	 *
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return bool
	 */
	public function performAction(ElementCriteriaModel $criteria)
	{
		// Figure out which element IDs we need to update
		if ($this->getParams()->status == BaseElementModel::ENABLED)
		{
			$criteria->status = BaseElementModel::DISABLED;
			$sqlNewStatus = '1';
		}
		else
		{
			$criteria->status = BaseElementModel::ENABLED;
			$sqlNewStatus = '0';
		}

		$elementIds = $criteria->ids();

		// Update their statuses
		craft()->db->createCommand()->update(
			'elements',
			array('enabled' => $sqlNewStatus),
			array('in', 'id', $elementIds)
		);

		// Clear their template caches
		craft()->templateCache->deleteCacheById($elementIds);

		$this->setMessage(Craft::t('Statuses updated.'));

		return true;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseSavableComponentType::defineParams()
	 *
	 * @return array
	 */
	protected function defineParams()
	{
		return array(
			'status' => array(AttributeType::Enum, 'values' => array(BaseElementModel::ENABLED, BaseElementModel::DISABLED), 'required' => true)
		);
	}
}

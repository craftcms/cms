<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\elementactions;

use Craft;
use craft\app\enums\AttributeType;
use craft\app\models\ElementCriteria as ElementCriteriaModel;

/**
 * Delete Element Action
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Delete extends BaseElementAction
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc ComponentTypeInterface::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('app', 'Delete…');
	}

	/**
	 * @inheritDoc ElementActionInterface::isDestructive()
	 *
	 * @return bool
	 */
	public function isDestructive()
	{
		return true;
	}

	/**
	 * @inheritDoc ElementActionInterface::getConfirmationMessage()
	 *
	 * @return string|null
	 */
	public function getConfirmationMessage()
	{
		return $this->getParams()->confirmationMessage;
	}

	/**
	 * @inheritDoc ElementActionInterface::performAction()
	 *
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return bool
	 */
	public function performAction(ElementCriteriaModel $criteria)
	{
		Craft::$app->elements->deleteElementById($criteria->ids());

		$this->setMessage($this->getParams()->successMessage);

		return true;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseElementAction::defineParams()
	 *
	 * @return array
	 */
	protected function defineParams()
	{
		return [
			'confirmationMessage' => [AttributeType::String],
			'successMessage'      => [AttributeType::String],
		];
	}
}

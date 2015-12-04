<?php
namespace Craft;

/**
 * The ElementIndexSettingsController class is a controller that handles various element index related actions.
 *
 * Note that all actions in the controller require an authenticated Craft session via {@link BaseController::allowAnonymous}.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.controllers
 * @since     2.5
 */
class ElementIndexSettingsController extends BaseElementsController
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns all the info needed by the Customize Sources modal.
	 */
	public function actionGetCustomizeSourcesModalData()
	{
		$this->requireAdmin();

		$elementType = $this->getElementType();
		$elementTypeClass = $elementType->getClassHandle();

		// Get the source info
		$sources = craft()->elementIndexes->getSources($elementTypeClass);

		foreach ($sources as &$source)
		{
			if (array_key_exists('heading', $source))
			{
				continue;
			}

			$tableAttributes = craft()->elementIndexes->getTableAttributes($elementTypeClass, $source['key']);
			$source['tableAttributes'] = array();

			foreach ($tableAttributes as $attribute)
			{
				$source['tableAttributes'][] = array($attribute[0], $attribute[1]['label']);
			}
		}

		// Get the available table attributes
		$availableTableAttributes = array();

		foreach (craft()->elementIndexes->getAvailableTableAttributes($elementTypeClass) as $key => $labelInfo)
		{
			$availableTableAttributes[] = array($key, Craft::t($labelInfo['label']));
		}

		$this->returnJson(array(
			'sources' => $sources,
			'availableTableAttributes' => $availableTableAttributes,
		));
	}

	/**
	 * Saves the Customize Sources modal settings.
	 */
	public function actionSaveCustomizeSourcesModalSettings()
	{
		$this->requireAjaxRequest();
		$this->requireAdmin();

		$elementType = $this->getElementType();
		$elementTypeClass = $elementType->getClassHandle();

		$sourceOrder = craft()->request->getPost('sourceOrder', array());
		$sources = craft()->request->getPost('sources', array());

		// Normalize to the way it's stored in the DB
		foreach ($sourceOrder as $i => $source)
		{
			if (isset($source['heading']))
			{
				$sourceOrder[$i] = array('heading', $source['heading']);
			}
			else
			{
				$sourceOrder[$i] = array('key', $source['key']);
			}
		}

		// Remove the blank table attributes
		foreach ($sources as &$source)
		{
			$source['tableAttributes'] = array_filter($source['tableAttributes']);
		}

		$settings = array(
			'sourceOrder' => $sourceOrder,
			'sources' => $sources,
		);

		if (craft()->elementIndexes->saveSettings($elementTypeClass, $settings))
		{
			$this->returnJson(array('success' => true));
		}
		else
		{
			$this->returnErrorJson(Craft::t('An unknown error occurred.'));
		}
	}
}

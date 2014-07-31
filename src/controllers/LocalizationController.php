<?php
namespace Craft;

craft()->requireEdition(Craft::Pro);

/**
 * Handles localization actions.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.controllers
 * @since     1.0
 */
class LocalizationController extends BaseController
{
	/**
	 * Adds a new a locale.
	 *
	 * @return void
	 */
	public function actionAddLocale()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$locale = craft()->request->getRequiredPost('id');
		$success = craft()->i18n->addSiteLocale($locale);
		$this->returnJson(array('success' => $success));
	}

	/**
	 * Saves the new locale order.
	 *
	 * @return void
	 */
	public function actionReorderLocales()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$locales = JsonHelper::decode(craft()->request->getRequiredPost('ids'));
		$success = craft()->i18n->reorderSiteLocales($locales);
		$this->returnJson(array('success' => $success));
	}

	/**
	 * Deletes a locale.
	 *
	 * @return void
	 */
	public function actionDeleteLocale()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$locale = craft()->request->getRequiredPost('id');
		$success = craft()->i18n->deleteSiteLocale($locale);
		$this->returnJson(array('success' => $success));
	}
}

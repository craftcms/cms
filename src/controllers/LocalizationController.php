<?php
namespace Craft;

craft()->requireEdition(Craft::Pro);

/**
 * The LocalizationController class is a controller that handles various localization related tasks such adding,
 * deleting and re-ordering locales in the control panel.
 *
 * Note that all actions in the controller require an authenticated Craft session via {@link BaseController::allowAnonymous}.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.controllers
 * @since     1.0
 */
class LocalizationController extends BaseController
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseController::init()
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function init()
	{
		// All localization related actions require an admin
		craft()->userSession->requireAdmin();
	}

	/**
	 * Adds a new a locale.
	 *
	 * @return null
	 */
	public function actionAddLocale()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$localeId = craft()->request->getRequiredPost('id');
		$success = craft()->i18n->addSiteLocale($localeId);
		$this->returnJson(array('success' => $success));
	}

	/**
	 * Saves the new locale order.
	 *
	 * @return null
	 */
	public function actionReorderLocales()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$localeIds = JsonHelper::decode(craft()->request->getRequiredPost('ids'));
		$success = craft()->i18n->reorderSiteLocales($localeIds);
		$this->returnJson(array('success' => $success));
	}

	/**
	 * Deletes a locale.
	 *
	 * @return null
	 */
	public function actionDeleteLocale()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$localeId = craft()->request->getRequiredPost('id');
		$transferContentTo = craft()->request->getPost('transferContentTo');

		$success = craft()->i18n->deleteSiteLocale($localeId, $transferContentTo);
		$this->returnJson(array('success' => $success));
	}
}

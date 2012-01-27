<?php

/**
 * Setup Controller
 */
class bSetupController extends bBaseController
{
	/**
	 * Init
	 */
	public function init()
	{
		// Return a 404 if Blocks is already setup
		if (!Blocks::app()->getConfig('devMode') && Blocks::app()->isSetup)
			throw new bHttpException(404);
	}

	/**
	 * Index action
	 */
	public function actionIndex()
	{
		$this->loadTemplate('_special/setup');
	}
}

<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craft\controllers;

use craft\web\Controller;
use yii\web\NotFoundHttpException;

class NotFoundController extends Controller
{
    // Properties
    // =========================================================================
    protected $allowAnonymous = true;

    // Public Methods
    // =========================================================================
    /**
     * Just throw an exception.
     *
     * @throws NotFoundHttpException
     */
    public function actionIndex()
    {
        throw new NotFoundHttpException();
    }
}

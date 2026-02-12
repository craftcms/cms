<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use craft\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class NotFoundController extends Controller
{
    /**
     * @inheritdoc
     */
    protected array|bool|int $allowAnonymous = true;

    /**
     * Just return a 404 error.
     *
     * @return Response
     * @throws NotFoundHttpException
     */
    public function actionIndex(): Response
    {
        throw new NotFoundHttpException();
    }
}

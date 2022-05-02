<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * RedirectController
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.13
 */
class RedirectController extends Controller
{
    /**
     * @inheritdoc
     */
    public array|bool|int $allowAnonymous = true;

    /**
     * Handles control panel logo and site icon uploads.
     *
     * @param string $url The hashed redirect URL
     * @param int $statusCode The response status code
     * @return Response
     */
    public function actionIndex(string $url, int $statusCode = 302): Response
    {
        $url = Craft::$app->getSecurity()->validateData($url);

        if (!$url) {
            throw new BadRequestHttpException('Invalid URL.');
        }

        return $this->redirect($url, $statusCode);
    }
}

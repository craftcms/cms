<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use craft\web\Controller;
use yii\web\Response;

/**
 * Class WellKnownController
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.11.0
 */
class WellKnownController extends Controller
{
    protected array|bool|int $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE;

    public function actionPasskeyEndpoints(): Response
    {
        // Just return an empty object to signal support for passkeys, w/o leaking the CP URL.
        $this->response->format = Response::FORMAT_JSON;
        $this->response->content = '{}';
        return $this->response;
    }
}

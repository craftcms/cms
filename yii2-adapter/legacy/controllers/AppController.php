<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\helpers\App;
use craft\web\Controller;
use CraftCms\Cms\License\License;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use InvalidArgumentException;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * The AppController class is a controller that handles various actions for Craft updates, control panel requests,
 * upgrading Craft editions and license requests.
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0
 * @internal
 */
class AppController extends Controller
{
    /**
     * @inheritdoc
     */
    protected array|bool|int $allowAnonymous = [
        'resource-js' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
    ];

    /**
     * Loads the given JavaScript resource URL and returns it.
     *
     * @param string $url
     * @return Response
     */
    public function actionResourceJs(string $url): Response
    {
        $assetManager = Craft::$app->getAssetManager();
        $baseUrl = Str::finish($assetManager->baseUrl, '/');

        if (!str_starts_with($url, $baseUrl)) {
            throw new BadRequestHttpException("$url does not appear to be a resource URL");
        }

        $resourceUri = preg_replace('/^(.*)\?.*/', '$1', substr($url, strlen($baseUrl)));

        if (!$assetManager->cacheSourcePaths) {
            // Close the PHP session in case this takes a while
            Session::save();

            $response = Http::create()->get($url);
            $this->response->setCacheHeaders();
            $this->response->getHeaders()->set('content-type', 'application/javascript');

            return $this->asRaw($response->getBody());
        }

        try {
            $publishedPath = App::resourcePathByUri($resourceUri);
        } catch (InvalidArgumentException $exception) {
            throw new BadRequestHttpException($exception->getMessage(), previous: $exception);
        }

        return $this->response->sendFile($publishedPath, null, [
            'inline' => true,
        ]);
    }
}

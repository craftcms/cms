<?php

/**
 * @link https://github.com/yii2tech
 *
 * @copyright Copyright (c) 2019 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace CraftCms\Yii2Adapter\Http;

use Closure;
use Craft;
use craft\helpers\App;
use CraftCms\Cms\Support\Json;
use CraftCms\Yii2Adapter\Web\DummyResponse;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use yii\base\ExitException as YiiExitException;
use yii\web\HttpException as YiiHttpException;

class LegacyMiddleware
{
    public function __construct(
        private Application $app,
    ) {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        app()->instance('request', $request);

        /**
         * Laravel applies \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull
         * globally, which causes issues in the legacy codebase. Here we restore all the
         * original empty strings that have been changed to null, back to empty strings.
         */
        $this->restoreEmptyStrings($request);

        try {
            $this->ensureCraftApp();

            /** @var \craft\web\Request $yiiRequest */
            $yiiRequest = Craft::createObject(App::webRequestConfig());
            $yiiRequest->csrfCookie = Craft::cookieConfig([], $yiiRequest);

            // Remove any token as it was already handled by Laravel's HandleTokenRequest
            $yiiRequest->setToken(null);

            Craft::$app->set('request', $yiiRequest);

            /**
             * Reset the user as it could have been set before.
             */
            Craft::$app->set('user', Craft::createObject(App::userConfig()));
            Craft::$app->run();

            return self::createResponse();
        } catch (YiiHttpException $e) {
            if ($e->statusCode === 404) {
                self::cleanup();

                // If Yii indicates page does not exist - pass its resolving to Laravel
                return $next($request);
            }

            throw $e;
        } catch (YiiExitException $e) {
            // In case Yii requests application termination - request is considered handled
            return self::createResponse();
        }
    }

    /**
     * Creates HTTP response for this middleware.
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP response instance.
     *
     *@see DummyResponse
     * @see \CraftCms\Yii2Adapter\Web\Response
     */
    public static function createResponse(): \Symfony\Component\HttpFoundation\Response
    {
        if (headers_sent()) {
            self::cleanup();

            return new DummyResponse();
        }

        $yiiResponse = Craft::$app ? Craft::$app->get('response') : null;

        self::cleanup();

        if ($yiiResponse instanceof \CraftCms\Yii2Adapter\Web\Response) {
            return $yiiResponse->getIlluminateResponse(true);
        }

        return new DummyResponse();
    }

    public static function cleanup(): void
    {
        app()->terminating(function() {
            if (!Craft::$app) {
                return;
            }

            Craft::$classMap = [];

            Craft::$app->getSession()->updateFlashCounters();

            Craft::setLogger(null);
            Craft::$app = null;
            app()->forgetInstance('Craft');
        });
    }

    private function ensureCraftApp(): void
    {
        if (class_exists(Craft::class, false) && Craft::$app) {
            return;
        }

        $craftApp = $this->app->make('Craft');

        if (!Craft::$app) {
            Craft::$app = $craftApp;
        }
    }

    private function restoreEmptyStrings(Request $request): void
    {
        $parameters = $request->isJson()
            ? Json::decode($request->getContent())
            : $_POST;

        foreach ($parameters ?? [] as $key => $value) {
            $this->restoreValue($request, $key, $value);
        }

        // in the ExtractNamespace middleware we're copying namespaced param values
        // from their namespace key to the general parameters location
        // (from request['parameters']['<namespace>'] to request['parameters'])
        // the above method will only restore the values in the namespaced params
        // now we should copy them over again
        if ($namespace = $request->header('X-Craft-Namespace')) {
            $request->merge($request->get($namespace));
        }
    }

    private function restoreValue(Request $request, $key, $value): void
    {
        if (!$request->has($key)) {
            return;
        }

        if (is_array($value)) {
            foreach ($value as $nestedKey => $nestedValue) {
                $this->restoreValue($request, $key . '.' . $nestedKey, $nestedValue);
            }
        }

        if ($value !== '' || !is_null($request->get($key))) {
            return;
        }

        $request->merge([
            $key => '',
        ]);
    }
}

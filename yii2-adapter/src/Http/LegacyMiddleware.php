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
use CraftCms\Yii2Adapter\Web\DummyResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use yii\base\ExitException as YiiExitException;
use yii\web\HttpException as YiiHttpException;

class LegacyMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        /**
         * Laravel applies \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull
         * globally, which causes issues in the legacy codebase. Here we restore all the
         * original empty strings that have been changed to null, back to empty strings.
         */
        $this->restoreEmptyStrings($request);

        try {
            /** @var \craft\web\Application $app */
            $app = app('Craft');

            /**
             * Reset the request as it could have been set before,
             * this can happen in tests or when Craft is run
             * through the Laravel artisan console.
             *
             * @var \craft\web\Request $request
             */
            $request = Craft::createObject(\craft\helpers\App::webRequestConfig());
            $request->csrfCookie = Craft::cookieConfig([], $request);
            $request->setIsConsoleRequest(false);
            $app->set('request', $request);

            /**
             * Reset the user as it could have been set before.
             *
             * @var \craft\web\User $user
             */
            $user = Craft::createObject(\craft\helpers\App::userConfig());
            $app->set('user', $user);

            $app->run();

            return $this->createResponse();
        } catch (YiiHttpException $e) {
            if ($e->statusCode === 404) {
                if (app()->hasDebugModeEnabled()) {
                    throw $e;
                }

                $this->cleanup();

                // If Yii indicates page does not exist - pass its resolving to Laravel
                return $next($request);
            }

            throw new HttpException($e->statusCode, $e->getMessage(), $e, [], $e->getCode());
        } catch (YiiExitException $e) {
            // In case Yii requests application termination - request is considered handled
            return $this->createResponse();
        }
    }

    /**
     * Creates HTTP response for this middleware.
     *
     * @return \Illuminate\Http\Response HTTP response instance.
     *
     *@see DummyResponse
     * @see \CraftCms\Yii2Adapter\Web\Response
     */
    protected function createResponse(): Response
    {
        if (headers_sent()) {
            $this->cleanup();

            return new DummyResponse();
        }

        $yiiResponse = Craft::$app ? Craft::$app->get('response') : null;

        $this->cleanup();

        if ($yiiResponse instanceof \CraftCms\Yii2Adapter\Web\Response) {
            return $yiiResponse->getIlluminateResponse(true);
        }

        return new DummyResponse();
    }

    protected function cleanup(): void
    {
        Craft::$classMap = [];

        Craft::$app->getSession()->updateFlashCounters();

        Craft::setLogger(null);
        Craft::$app = null;
        app()->forgetInstance('Craft');
    }

    private function restoreEmptyStrings(Request $request): void
    {
        $parameters = $request->isJson()
            ? json_decode($request->getContent(), true)
            : $_POST;

        foreach ($parameters as $key => $value) {
            $this->restoreValue($request, $key, $value);
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

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
        if ($request->uri()->path() === 'index.php' && $request->has('p')) {
            $internal = Request::create(
                uri: $request->get('p'),
                method: $request->method(),
                parameters: $request->except('p'),
                cookies: $request->cookies->all(),
                files: $request->allFiles(),
                server: $request->server->all(),
                content: $request->getContent(),
            );

            return $this->app->handle($internal);
        }

        /**
         * Laravel applies \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull
         * globally, which causes issues in the legacy codebase. Here we restore all the
         * original empty strings that have been changed to null, back to empty strings.
         */
        $this->restoreEmptyStrings($request);

        try {
            /** @var \craft\web\Request $request */
            $request = Craft::$app->get('request');

            // Remove any token as it was already handled by Laravel's HandleTokenRequest
            $request->setToken(null);

            Craft::$app->set('request', $request);

            /**
             * Reset the user as it could have been set before.
             */
            Craft::$app->set('user', Craft::createObject(App::userConfig()));
            Craft::$app->run();

            return $this->createResponse();
        } catch (YiiHttpException $e) {
            if ($e->statusCode === 404) {
                $this->cleanup();

                // If Yii indicates page does not exist - pass its resolving to Laravel
                return $next($request);
            }

            throw $e;
        } catch (YiiExitException $e) {
            // In case Yii requests application termination - request is considered handled
            return $this->createResponse();
        }
    }

    /**
     * Creates HTTP response for this middleware.
     *
     * @return Response HTTP response instance.
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
        $this->app->terminating(function() {
            Craft::$classMap = [];

            Craft::$app->getSession()->updateFlashCounters();

            Craft::setLogger(null);
            Craft::$app = null;
            $this->app->forgetInstance('Craft');
        });
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

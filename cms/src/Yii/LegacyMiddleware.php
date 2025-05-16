<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2019 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace Craft\Cms\Yii;

use Closure;
use Craft;
use Craft\Cms\Yii\Web\DummyResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use yii\base\ExitException as YiiExitException;
use yii\web\HttpException as YiiHttpException;

class LegacyMiddleware
{
    /**
     * Handle an incoming request, attempting to resolve it via Yii web application.
     *
     * @param  \Illuminate\Http\Request  $request request to be processed.
     * @param  \Closure  $next  next pipeline request handler.
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        try {
            /** @var \craft\web\Application $app */
            $app = app('Craft');
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
     * @see \Craft\Cms\Yii\Web\Response
     * @see DummyResponse
     *
     * @return \Illuminate\Http\Response HTTP response instance.
     */
    protected function createResponse(): Response
    {
        if (headers_sent()) {
            $this->cleanup();

            return new DummyResponse();
        }

        $yiiResponse = Craft::$app ? Craft::$app->get('response') : null;

        $this->cleanup();

        if ($yiiResponse instanceof Craft\Cms\Yii\Web\Response) {
            return $yiiResponse->getIlluminateResponse(true);
        }

        return new DummyResponse();
    }

    protected function cleanup(): void
    {
        Craft::$classMap = [];
        Craft::$aliases = [];

        Craft::setLogger(null);
        Craft::$app = null;
        Craft::$container = null;
    }
}

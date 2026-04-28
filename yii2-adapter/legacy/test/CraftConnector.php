<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test;

use Codeception\Exception\ConfigurationException;
use Codeception\Lib\Connector\Yii2;
use Codeception\Util\Debug;
use Craft;
use craft\helpers\Db;
use craft\web\View;
use CraftCms\Cms\Plugin\Contracts\PluginInterface;
use CraftCms\Cms\Plugin\Exceptions\InvalidPluginException;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB as DbFacade;
use Illuminate\Support\Facades\Session;
use Symfony\Component\BrowserKit\Response;
use yii\base\ExitException;
use yii\base\Module;
use yii\base\UserException;
use yii\log\Logger;
use yii\mail\MessageInterface;
use yii\web\Application;

/**
 * Class CraftConnector
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 *
 * @since 3.2.0
 */
class CraftConnector extends Yii2
{
    protected array $emails = [];

    /**
     * {@inheritdoc}
     */
    public function getEmails(): array
    {
        return $this->emails;
    }

    /**
     * {@inheritdoc}
     */
    protected function mockMailer(array $config): array
    {
        $config = parent::mockMailer($config);
        $config['components']['mailer'] = array_merge($config['components']['mailer'], [
            'class' => TestMailer::class, 'callback' => function(MessageInterface $message) {
                $this->emails[] = $message;
            },
        ]);

        return $config;
    }

    /**
     * @throws InvalidPluginException
     */
    protected function resetRequest(Application $app): void
    {
        parent::resetRequest($app);
        $app->getRequest()->setIsConsoleRequest(false);

        // Reset the view object
        $app->set('view', new View());

        /** @var Module $module */
        foreach (Craft::$app->getModules(true) as $module) {
            $moduleClass = get_class($module);
            $moduleId = $module->id;

            if ($module instanceof PluginInterface) {
                $plugins = app(Plugins::class);

                // Follow the same error handling as Craft does natively.
                if (($info = $plugins->getStoredPluginInfo($moduleId)) === null) {
                    throw new InvalidPluginException($moduleId);
                }

                $module = $plugins->createPlugin($moduleId, $info);
            } else {
                $module = new $moduleClass($moduleId, Craft::$app);
            }

            /** @var class-string<Module> $moduleClass */
            $moduleClass::setInstance($module);
            Craft::$app->setModule($moduleId, $module);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function resetApplication($closeSession = true): void
    {
        parent::resetApplication($closeSession);
        Db::reset();
        DbFacade::disconnect();
        DbFacade::disconnect('db2');
        Session::invalidate();
        unset($_SERVER['CRAFT_SITE'], $_SERVER['CRAFT_SITE_UPPER']);
        Cache::lock(ProjectConfig::MUTEX_NAME)->forceRelease();
    }

    /**
     * Closes the db connection after initializing Craft. The Yii2 module will
     * try to initialize transaction listeners before each test. If we don't
     * close the connection first, those listeners will never get picked up.
     * We'll open the connection after all of the transaction listeners are
     * registered.
     *
     * {@inheritDoc}
     */
    public function startApp(?Logger $logger = null): void
    {
        parent::startApp($logger);

        Craft::$app->db->close();
        Craft::$app->db2->close();
    }

    public function doRequest(object $request): Response
    {
        /**
         * Fake Laravel request
         */
        app()->bind('request', fn() => Request::create(
            uri: $request->getUri(),
            method: $request->getMethod(),
            parameters: $request->getParameters(),
            cookies: $request->getCookies(),
            files: $request->getFiles(),
            server: $request->getServer(),
            content: $request->getContent()
        ));

        // From here on out everything is the same as parent method

        $_COOKIE = $request->getCookies();
        $_SERVER = $request->getServer();
        $_FILES = $this->remapFiles($request->getFiles());
        $_REQUEST = $this->remapRequestParameters($request->getParameters());
        $_POST = $_GET = [];

        if (strtoupper($request->getMethod()) === 'GET') {
            $_GET = $_REQUEST;
        } else {
            $_POST = $_REQUEST;
        }

        $uri = $request->getUri();

        $pathString = parse_url($uri, PHP_URL_PATH);
        $queryString = parse_url($uri, PHP_URL_QUERY);
        $_SERVER['REQUEST_URI'] = $queryString === null ? $pathString : $pathString . '?' . $queryString;
        $_SERVER['REQUEST_METHOD'] = strtoupper($request->getMethod());
        $_SERVER['QUERY_STRING'] = (string) $queryString;

        parse_str($queryString ?: '', $params);
        foreach ($params as $k => $v) {
            $_GET[$k] = $v;
        }

        ob_start();

        $this->beforeRequest();

        $app = $this->getApplication();
        if (!$app instanceof Application) {
            throw new ConfigurationException('Application is not a web application');
        }

        // disabling logging. Logs are slowing test execution down
        foreach ($app->log->targets as $target) {
            $target->enabled = false;
        }

        $yiiRequest = $app->getRequest();
        if ($request->getContent() !== null) {
            $yiiRequest->setRawBody($request->getContent());
            /** @phpstan-ignore-next-line */
            $yiiRequest->setBodyParams(null);
        } else {
            /** @phpstan-ignore-next-line */
            $yiiRequest->setRawBody(null);
            $yiiRequest->setBodyParams($_POST);
        }
        $yiiRequest->setQueryParams($_GET);

        try {
            /*
             * This is basically equivalent to $app->run() without sending the response.
             * Sending the response is problematic because it tries to send headers.
             */
            $app->trigger($app::EVENT_BEFORE_REQUEST);
            $response = $app->handleRequest($yiiRequest);
            $app->trigger($app::EVENT_AFTER_REQUEST);
            $response->send();
        } catch (Exception $e) {
            if ($e instanceof UserException) {
                // Don't discard output and pass exception handling to Yii to be able
                // to expect error response codes in tests.
                $app->errorHandler->discardExistingOutput = false;
                $app->errorHandler->handleException($e);
            } elseif (!$e instanceof ExitException) {
                // for exceptions not related to Http, we pass them to Codeception
                throw $e;
            }
            $response = $app->response;
        }

        $this->encodeCookies($response, $yiiRequest, $app->security);

        if ($response->isRedirection) {
            Debug::debug('[Redirect with headers]' . print_r($response->getHeaders()->toArray(), true));
        }

        $content = ob_get_clean();

        // Addition: Get content from response as Yii adapter doesn't output the content

        $content = empty($content) ? $response->content ?? '' : $content;

        // /Addition

        /** @phpstan-ignore-next-line */
        if (empty($content) && !empty($response->content) && !isset($response->stream)) {
            throw new Exception('No content was sent from Yii application');
        }

        return new Response($content, $response->statusCode, $response->getHeaders()->toArray());
    }
}

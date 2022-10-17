<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console;

use Craft;
use craft\base\ApplicationTrait;
use craft\console\controllers\HelpController;
use craft\db\Query;
use craft\db\Table;
use craft\errors\MissingComponentException;
use craft\helpers\Console;
use craft\queue\QueueLogBehavior;
use IntlDateFormatter;
use IntlException;
use Throwable;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\Response as BaseResponse;
use yii\console\controllers\CacheController;
use yii\console\controllers\MigrateController;
use yii\console\Response;

/**
 * Craft Console Application class
 *
 * An instance of the Console Application class is globally accessible to console requests in Craft via [[\Craft::$app|`Craft::$app`]].
 *
 * @property Request $request The request component
 * @property User $user The user component
 * @method Request getRequest() Returns the request component.
 * @method Response getResponse() Returns the response component.
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Application extends \yii\console\Application
{
    use ApplicationTrait;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->state = self::STATE_INIT;
        $this->_preInit();
        parent::init();
        $this->_postInit();
    }

    /**
     * @inheritdoc
     */
    public function bootstrap(): void
    {
        // Ensure that the request component has been instantiated
        if (!$this->has('request', true)) {
            $this->getRequest();
        }

        parent::bootstrap();
    }

    /**
     * @inheritdoc
     */
    public function runAction($route, $params = []): int|BaseResponse|null
    {
        if (!$this->getIsInstalled(true) && $this->_requireInfoTable($route, $params)) {
            // Is the connection valid at least?
            if (!$this->getIsDbConnectionValid()) {
                Console::outputWarning('Craft can’t connect to the database. Check your connection settings.');
            } else {
                $infoTable = $this->getDb()->getSchema()->getRawTableName(Table::INFO);
                // Figure out the exception that is getting thrown
                $e = null;
                try {
                    (new Query())->from([Table::INFO])->where(['id' => 1])->one();
                } catch (Throwable $e) {
                    $e = $e->getPrevious() ?? $e;
                }
                Console::outputWarning("Craft can’t fetch the `$infoTable` table row." . ($e ? PHP_EOL . 'Exception: ' . $e->getMessage() : ''), false);
            }
        }

        return parent::runAction($route, $params);
    }

    /**
     * @inheritdoc
     */
    public function setTimeZone($value): void
    {
        parent::setTimeZone($value);

        if ($value !== 'UTC') {
            // Make sure that ICU supports this timezone
            try {
                /** @noinspection PhpExpressionResultUnusedInspection */
                /** @phpstan-ignore-next-line */
                new IntlDateFormatter($this->language, IntlDateFormatter::NONE, IntlDateFormatter::NONE);
            } catch (IntlException) {
                Craft::warning("Time zone “{$value}” does not appear to be supported by ICU: " . intl_get_error_message());
                parent::setTimeZone('UTC');
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function handleRequest($request)
    {
        // Disable read/write splitting for all console requests
        $this->getDb()->enableReplicas = false;

        return parent::handleRequest($request);
    }

    /**
     * Returns the configuration of the built-in commands.
     *
     * @return array The configuration of the built-in commands.
     */
    public function coreCommands(): array
    {
        return [
            'help' => HelpController::class,
            'migrate' => MigrateController::class,
            'cache' => CacheController::class,
        ];
    }

    /**
     * @throws MissingComponentException
     */
    public function getSession()
    {
        throw new MissingComponentException('Session does not exist in a console request.');
    }

    /**
     * Returns the user component.
     *
     * @return User
     */
    public function getUser(): User
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('user');
    }

    /**
     * Returns the component instance with the specified ID.
     *
     * @param string $id component ID (e.g. `db`).
     * @param bool $throwException whether to throw an exception if `$id` is not registered with the locator before.
     * @return object|null the component of the specified ID. If `$throwException` is false and `$id`
     * is not registered before, null will be returned.
     * @throws InvalidConfigException if `$id` refers to a nonexistent component ID
     * @see has()
     * @see set()
     */
    public function get($id, $throwException = true): ?object
    {
        // Is this the first time the queue component is requested?
        $isFirstQueue = $id === 'queue' && !$this->has($id, true);

        $component = parent::get($id, $throwException);

        if ($isFirstQueue && $component instanceof Component) {
            $component->attachBehavior('queueLogger', QueueLogBehavior::class);
        }

        return $component;
    }

    private function _requireInfoTable(string $route, array $params): bool
    {
        if (isset($params['help'])) {
            return false;
        }

        [$firstSeg] = explode('/', $route, 2);
        return !in_array($firstSeg, ['install', 'setup', 'db', 'help'], true);
    }
}

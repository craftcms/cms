<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console;

use Craft;
use craft\base\ApplicationTrait;
use craft\errors\MissingComponentException;
use craft\queue\QueueLogBehavior;
use yii\base\Component;
use yii\console\controllers\CacheController;
use yii\console\controllers\HelpController;
use yii\console\controllers\MigrateController;
use yii\console\Response;

/**
 * Craft Console Application class
 *
 * An instance of the Console Application class is globally accessible to console requests in Craft via [[\Craft::$app|<code>Craft::$app</code>]].
 *
 * @property Request $request The request component
 * @property User $user The user component
 * @method Request getRequest()      Returns the request component.
 * @method Response getResponse()     Returns the response component.
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Application extends \yii\console\Application
{
    // Traits
    // =========================================================================

    use ApplicationTrait;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        Craft::$app = $this;
        parent::__construct($config);
    }

    /**
     * Initializes the console app by creating the command runner.
     */
    public function init()
    {
        $this->state = self::STATE_INIT;
        // Important that we call $this->_init() before parent::init(), so that it's run before bootstrap()
        // in case bootstrap() ends up loading a module that loads Twig, configuring Twig with the wrong timezone
        $this->_init();
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function bootstrap()
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
    public function setTimeZone($value)
    {
        parent::setTimeZone($value);

        if ($value !== 'UTC' && $this->getI18n()->getIsIntlLoaded()) {
            // Make sure that ICU supports this timezone
            try {
                new \IntlDateFormatter($this->language, \IntlDateFormatter::NONE, \IntlDateFormatter::NONE);
            } catch (\IntlException $e) {
                Craft::warning("Time zone \"{$value}\" does not appear to be supported by ICU: ".intl_get_error_message());
                parent::setTimeZone('UTC');
            }
        }
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
    public function getUser()
    {
        return $this->get('user');
    }

    /**
     * @inheritdoc
     */
    public function get($id, $throwException = true)
    {
        // Is this the first time the queue component is requested?
        $isFirstQueue = $id === 'queue' && !$this->has($id, true);

        $component = parent::get($id, $throwException);

        if ($isFirstQueue && $component instanceof Component) {
            $component->attachBehavior('queueLogger', QueueLogBehavior::class);
        }

        return $component;
    }
}

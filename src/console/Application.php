<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craft\console;

use Craft;
use craft\base\ApplicationTrait;
use craft\errors\MissingComponentException;
use yii\console\controllers\CacheController;
use yii\console\controllers\HelpController;
use yii\console\controllers\MigrateController;
use yii\console\Response;

/**
 * Craft Console Application class
 *
 * @property Request $request          The request component
 * @property User    $user             The user component
 *
 * @method Request   getRequest()      Returns the request component.
 * @method Response  getResponse()     Returns the response component.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
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
     *
     * @return void
     */
    public function init()
    {
        parent::init();

        // Set default timezone to UTC
        date_default_timezone_set('UTC');

        $this->_init();
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
}

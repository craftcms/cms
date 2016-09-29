<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\console;

use Craft;
use craft\app\base\ApplicationTrait;

/**
 * Craft Console Application class
 *
 * @property Request $request          The request component
 * @property User    $user             The user component
 *
 * @method Request                                getRequest()      Returns the request component.
 * @method User                                   getUser()         Returns the user component.
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
     * Constructor.
     *
     * @param array $config
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
    public function coreCommands()
    {
        return [
            'help' => \yii\console\controllers\HelpController::class,
            'migrate' => \yii\console\controllers\MigrateController::class,
            'cache' => \yii\console\controllers\CacheController::class,
        ];
    }
}

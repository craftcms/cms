<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test;


use Codeception\Lib\Connector\Yii2;
use Yii;
use yii\base\InvalidConfigException;
use yii\mail\MessageInterface;
use yii\web\Application;


/**
 * CraftConnector
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class CraftConnector extends Yii2
{
    // Private properties
    // =========================================================================

    /**
     * @var array $emails
     */
    protected $emails;

    // Public functions
    // =========================================================================

    /**
     * @throws InvalidConfigException
     */
    public function startApp()
    {
        parent::startApp();
        \Craft::$app = Yii::$app;

        \Craft::$app->set('mailer', ['class' => TestMailer::class, 'callback' => function (MessageInterface $message) {
            $this->emails[] = $message;
        }]);
    }

    /**
     * @inheritdoc
     */
    public function resetApplication()
    {
        parent::resetApplication();
        TestSetup::tearDownCraft();
    }

    /**
     * @param Application $app
     */
    public function resetRequest(Application $app)
    {
        parent::resetRequest($app);

        $app->getRequest()->setIsConsoleRequest(false);
    }
}

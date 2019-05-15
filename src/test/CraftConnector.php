<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test;

use Craft;
use Codeception\Lib\Connector\Yii2;
use Yii;
use yii\mail\MessageInterface;
use yii\web\Application;

/**
 * Class CraftConnector
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.1
 */
class CraftConnector extends Yii2
{
    // Public Properties
    // =========================================================================

    /**
     * @var array|MessageInterface
     */
    protected $emails;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getEmails() : array
    {
        return $this->emails;
    }

    /**
     *
     */
    public function startApp()
    {
        parent::startApp();

        Craft::$app = Yii::$app;

        Craft::$app->set('mailer', ['class' => TestMailer::class, 'callback' => function (MessageInterface $message) {
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

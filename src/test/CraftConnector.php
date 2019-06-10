<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test;

use Codeception\Lib\Connector\Yii2;
use Craft;
use craft\base\Plugin;
use yii\base\Module;
use yii\mail\MessageInterface;
use yii\web\Application;

/**
 * Class CraftConnector
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
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
    public function getEmails(): array
    {
        return $this->emails;
    }

    /**
     *
     */
    public function startApp()
    {
        parent::startApp();

        Craft::$app->set('mailer', [
            'class' => TestMailer::class, 'callback' => function(MessageInterface $message) {
                $this->emails[] = $message;
            }
        ]);
    }

    /**
     * @param Application $app
     * @throws \craft\errors\InvalidPluginException
     */
    public function resetRequest(Application $app)
    {
        parent::resetRequest($app);
        $app->getRequest()->setIsConsoleRequest(false);

        $modules = Craft::$app->getModules();
        /* @var Module $module */
        foreach ($modules as $module) {
            $moduleClass = get_class($module);
            $moduleId = $module->id;

            if ($module instanceof Plugin) {
                $module = Craft::$app->getPlugins()->createPlugin($moduleId);
            } else {
                $module = new $moduleClass($moduleId, Craft::$app);
            }

            $moduleClass::setInstance(
                $module
            );
            Craft::$app->setModule($moduleId, $module);
        }
    }
}

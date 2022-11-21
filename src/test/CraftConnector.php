<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test;

use Codeception\Exception\ConfigurationException;
use Codeception\Lib\Connector\Yii2;
use Craft;
use craft\base\PluginInterface;
use craft\errors\InvalidPluginException;
use craft\helpers\Db;
use craft\helpers\Session;
use craft\web\View;
use yii\base\Module;
use yii\mail\MessageInterface;
use yii\web\Application;

/**
 * Class CraftConnector
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2.0
 */
class CraftConnector extends Yii2
{
    /**
     * @var array
     */
    protected array $emails = [];

    /**
     * @inheritdoc
     */
    public function getEmails(): array
    {
        return $this->emails;
    }

    /**
     * We override to prevent a bug with the matching of user agent and session.
     *
     * @param mixed $user
     * @param bool $disableRequiredUserAgent
     * @throws ConfigurationException
     */
    public function findAndLoginUser(mixed $user, bool $disableRequiredUserAgent = true): void
    {
        $oldRequirement = Craft::$app->getConfig()->getGeneral()->requireUserAgentAndIpForSession;
        if ($disableRequiredUserAgent) {
            Craft::$app->getConfig()->getGeneral()->requireUserAgentAndIpForSession = false;
        }

        parent::findAndLoginUser($user);

        if ($disableRequiredUserAgent) {
            Craft::$app->getConfig()->getGeneral()->requireUserAgentAndIpForSession = $oldRequirement;
        }
    }

    /**
     * @inheritdoc
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
     * @param Application $app
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
                $plugins = Craft::$app->getPlugins();

                // Follow the same error handling as Craft does natively.
                if (($info = $plugins->getStoredPluginInfo($moduleId)) === null) {
                    throw new InvalidPluginException($moduleId);
                }

                $module = $plugins->createPlugin($moduleId, $info);
            } else {
                $module = new $moduleClass($moduleId, Craft::$app);
            }

            /** @var string|Module $moduleClass */
            /** @phpstan-var class-string<Module>|Module $moduleClass */
            $moduleClass::setInstance(
                $module
            );
            Craft::$app->setModule($moduleId, $module);
        }
    }

    /**
     * @inheritdoc
     */
    public function resetApplication($closeSession = true): void
    {
        parent::resetApplication($closeSession);
        Db::reset();
        Session::reset();
    }
}

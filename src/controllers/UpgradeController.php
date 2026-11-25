<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\helpers\Json;
use craft\utilities\Upgrade;
use craft\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @internal
 */
class UpgradeController extends Controller
{
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        if (Craft::$app->getUtilities()->checkAuthorization(Upgrade::class) === false) {
            throw new ForbiddenHttpException(sprintf('User not permitted to access the “%s” utility.', Upgrade::displayName()));
        }

        return true;
    }

    public function actionPrepComposerJson(): Response
    {
        $versions = $this->request->getBodyParam('versions');
        $composerService = Craft::$app->getComposer();
        $jsonPath = $composerService->getJsonPath();
        $json = file_get_contents($jsonPath);
        $config = Json::decode($json);

        $config['require']['craftcms/cms'] = $versions['craft'];

        if (isset($versions['php'], $config['config']['platform']['php'])) {
            $config['config']['platform']['php'] = $versions['php'];
        }

        if (!empty($versions['plugins'])) {
            $pluginsService = Craft::$app->getPlugins();
            foreach ($versions['plugins'] as $handle => $version) {
                $info = $pluginsService->getPluginInfo($handle);
                $config['require'][$info['packageName']] = $version;
                unset($config['require-dev'][$info['packageName']]);
            }
        }

        if ($config['config']['sort-packages'] ?? false) {
            $composerService->sortPackages($config['require']);
        }

        $json = Json::encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $indent = Json::detectIndent(file_get_contents($jsonPath));
        $json = Json::reindent($json, $indent);

        return $this->asJson([
            'json' => $json,
        ]);
    }
}

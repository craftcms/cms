<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\controllers;

use Composer\IO\BufferIO;
use Composer\Package\Version\VersionParser;
use Craft;
use craft\helpers\ArrayHelper;
use craft\web\Controller;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/**
 * Class ComposerController.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class ComposerController extends Controller
{
    protected $allowAnonymous = true;

    // Public Methods
    // =========================================================================

    /**
     * Installs Composer packages.
     *
     * @param string $packages The package names/versions to install. Use the format `name1:version1,name2:version2`
     *
     * @return Response
     * @throws ServerErrorHttpException if composer.json can't be located
     */
    public function actionInstall(string $packages): Response
    {
        // Normalize the requirements as name => version pairs
        $parsedPackages = (new VersionParser)->parseNameVersionPairs(explode(',', $packages));
        $requirements = ArrayHelper::getPairs($parsedPackages, 'name', 'version');

        $io = new BufferIO();

        try {
            Craft::$app->getComposer()->install($requirements, $io);
        } catch (\Exception $e) {
            throw new ServerErrorHttpException(Craft::t('app', 'There was a problem installing the requested packages.'), 0, $e);
        }

        return $this->asJson([
            'success' => true,
            'output' => strip_tags($io->getOutput())
        ]);
    }

    /**
     * Uninstalls Composer packages.
     *
     * @param string $packages The package names. Use the format `name1,name2`
     *
     * @return Response
     * @throws ServerErrorHttpException if composer.json can't be located
     */
    public function actionUninstall(string $packages): Response
    {
        $packages = explode(',', $packages);

        $io = new BufferIO();

        try {
            Craft::$app->getComposer()->uninstall($packages, $io);
        } catch (\Exception $e) {
            throw new ServerErrorHttpException(Craft::t('app', 'There was a problem uninstalling the requested packages.'), 0, $e);
        }

        return $this->asJson([
            'success' => true,
            'output' => strip_tags($io->getOutput())
        ]);
    }

    /**
     * Optimizes the Composer autoloader
     *
     * @return Response
     * @throws ServerErrorHttpException if composer.json can't be located
     */
    public function actionOptimize(): Response
    {
        $io = new BufferIO();

        try {
            Craft::$app->getComposer()->optimize($io);
        } catch (\Exception $e) {
            throw new ServerErrorHttpException(Craft::t('app', 'There was a problem optimizing the Composer autoloader.'), 0, $e);
        }

        return $this->asJson([
            'success' => true,
            'output' => strip_tags($io->getOutput())
        ]);
    }
}

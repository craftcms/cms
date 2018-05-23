<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\web\Controller;
use Symfony\Component\Yaml\Yaml;
use yii\web\Response;

/**
 * The ContentModelController class is a controller that handles various content model related actions.
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class SystemConfigController extends Controller
{
    // Public Methods
    // =========================================================================

    public function init()
    {
        $this->requireAdmin();
        parent::init();
    }

    /**
     * Renders and returns an element index container, plus its first batch of elements.
     *
     * @return Response
     */
    public function actionGenerateBlueprint(): Response
    {
        $systemConfigService = Craft::$app->getSystemConfig();
        $siteData = $systemConfigService->getSystemConfigData();
        $siteYaml = Yaml::dump($siteData, 14, 2);
echo '<pre>';

        print_r($siteYaml);
        die('-');
    }
}

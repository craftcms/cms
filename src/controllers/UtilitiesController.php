<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\controllers;

use Craft;
use craft\web\Controller;
use craft\tools\AssetIndex;
use craft\tools\ClearCaches;
use craft\tools\DbBackup;
use craft\tools\FindAndReplace;
use craft\tools\SearchIndex;
use craft\helpers\App;
use GuzzleHttp\Client;
use Imagine\Gd\Imagine;

class UtilitiesController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * Utilities Index
     *
     * @return null
     */
    public function actionIndex()
    {
        $tools = [];

        // Only include the Update Asset Indexes tool if there are any asset volumes
        if (count(Craft::$app->getVolumes()->getAllVolumes()) !== 0) {
            $tools[] = new AssetIndex();
        }

        $tools[] = new ClearCaches();
        $tools[] = new DbBackup();
        $tools[] = new FindAndReplace();
        $tools[] = new SearchIndex();

        return $this->renderTemplate('utilities/_index', [
            'tools' => $tools
        ]);
    }

    /**
     * System Report
     *
     * @return null
     */
    public function actionSystemReport()
    {
        return $this->renderTemplate('utilities/_system-report', array(
            'craftVersion' => Craft::$app->version,
            'craftEdition' => 'Craft '.App::editionName(Craft::$app->getEdition()),
            'packages' => [
                'Yii' => \Yii::getVersion(),
                'Twig' => \Twig_Environment::VERSION,
                'Guzzle' => Client::VERSION,
                'Imagine' => Imagine::VERSION,
            ],
            'plugins' => $this->_getPlugins(),
            'requirements' => $this->_getRequirementResults(),
        ));
    }

    /**
     * PHP info
     *
     * @return null
     */
    public function actionPhpInfo()
    {
        return $this->renderTemplate('utilities/_php-info', [
            'phpInfo' => $this->_getPhpInfo(),
        ]);
    }

    /**
     * Deprecation Errors
     *
     * @return null
     */
    public function actionDeprecationErrors()
    {
        Craft::$app->getView()->registerCssResource('css/deprecator.css');
        Craft::$app->getView()->registerJsResource('js/deprecator.js');

        return $this->renderTemplate('utilities/deprecation-errors/index', [
            'logs' => Craft::$app->deprecator->getLogs()
        ]);
    }

    /**
     * View stack trace for a deprecator log entry.
     *
     * @return null
     */
    public function actionGetDeprecationErrorTracesModal()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $logId = Craft::$app->request->getRequiredParam('logId');
        $log = Craft::$app->deprecator->getLogById($logId);

        $html = $this->renderTemplate('utilities/deprecation-errors/_tracesmodal',
            array('log' => $log)
        , true);

        return $this->asJson([
            'html' => $html
        ]);
    }

    /**
     * Deletes all deprecation errors.
     *
     * @return null
     */
    public function actionDeleteAllDeprecationErrors()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        Craft::$app->deprecator->deleteAllLogs();
        /*Craft::$app->end();*/

        return $this->asJson(['success' => true]);
    }

    /**
     * Deletes a deprecation error.
     *
     * @return null
     */
    public function actionDeleteDeprecationError()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $logId = Craft::$app->getRequest()->getRequiredBodyParam('logId');

        Craft::$app->deprecator->deleteLogById($logId);
        /*Craft::$app->end();*/

        return $this->asJson(['success' => true]);
    }

    // Private Methods
    // =========================================================================

    /**
     * Parses and returns the PHP info.
     *
     * @return array
     */
    private function _getPhpInfo()
    {
        Craft::$app->getConfig()->maxPowerCaptain();

        ob_start();
        phpinfo(-1);
        $phpInfo = ob_get_clean();

        $phpInfo = preg_replace(
            [
                '#^.*<body>(.*)</body>.*$#ms',
                '#<h2>PHP License</h2>.*$#ms',
                '#<h1>Configuration</h1>#',
                "#\r?\n#",
                '#</(h1|h2|h3|tr)>#',
                '# +<#',
                "#[ \t]+#",
                '#&nbsp;#',
                '#  +#',
                '# class=".*?"#',
                '%&#039;%',
                '#<tr>(?:.*?)"src="(?:.*?)=(.*?)" alt="PHP Logo" /></a><h1>PHP Version (.*?)</h1>(?:\n+?)</td></tr>#',
                '#<h1><a href="(?:.*?)\?=(.*?)">PHP Credits</a></h1>#',
                '#<tr>(?:.*?)" src="(?:.*?)=(.*?)"(?:.*?)Zend Engine (.*?),(?:.*?)</tr>#',
                '# +#',
                '#<tr>#',
                '#</tr>#'
            ],
            [
                '$1',
                '',
                '',
                '',
                '</$1>'."\n",
                '<',
                ' ',
                ' ',
                ' ',
                '',
                ' ',
                '<h2>PHP Configuration</h2>'."\n".'<tr><td>PHP Version</td><td>$2</td></tr>'."\n".'<tr><td>PHP Egg</td><td>$1</td></tr>',
                '<tr><td>PHP Credits Egg</td><td>$1</td></tr>',
                '<tr><td>Zend Engine</td><td>$2</td></tr>'."\n".'<tr><td>Zend Egg</td><td>$1</td></tr>',
                ' ',
                '%S%',
                '%E%'
            ],
            $phpInfo
        );

        $sections = explode('<h2>', strip_tags($phpInfo, '<h2><th><td>'));
        unset($sections[0]);

        $phpInfo = [];
        foreach ($sections as $section) {
            $heading = substr($section, 0, strpos($section, '</h2>'));

            preg_match_all(
                '#%S%(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?%E%#',
                $section,
                $parts,
                PREG_SET_ORDER
            );

            foreach ($parts as $row) {
                if (!isset($row[2])) {
                    continue;
                } else if (!isset($row[3]) || $row[2] == $row[3]) {
                    $value = $row[2];
                } else {
                    $value = array_slice($row, 2);
                }

                $phpInfo[$heading][$row[1]] = $value;
            }
        }

        return $phpInfo;
    }

    /**
     * Returns info about the installed plugins
     *
     * @return array
     */
    private function _getPlugins()
    {
        $plugins = [];

        foreach (Craft::$app->getPlugins()->getAllPlugins() as $plugin) {
            /** @var Plugin $plugin */
            $plugins[] = [
                'name' => $plugin->name,
                'version' => $plugin->version,
                'developer' => $plugin->developer,
                'developerUrl' => $plugin->developerUrl,
            ];
        }

        return $plugins;
    }

    /**
     * Runs the requirements checker and returns its results.
     *
     * @return array
     */
    private function _getRequirementResults()
    {
        require_once Craft::$app->getBasePath().DIRECTORY_SEPARATOR.'requirements'.DIRECTORY_SEPARATOR.'RequirementsChecker.php';

        $reqCheck = new \RequirementsChecker();
        $reqCheck->checkCraft();

        return $reqCheck->getResult()['requirements'];
    }
}

<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\d3;

use Craft;
use craft\helpers\ChartHelper;
use craft\helpers\Json;
use craft\web\AssetBundle;
use craft\web\View;

/**
 * D3 asset bundle.
 */
class D3Asset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = '@lib/d3';

        $this->js = [
            'd3.js',
        ];

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view)
    {
        parent::registerAssetFiles($view);

        // Add locale definition JS variables
        $libPath = Craft::getAlias('@lib');
        $js = 'window.d3FormatLocaleDefinition = '.$this->formatDef($libPath.'/d3-format').';';
        $js .= 'window.d3TimeFormatLocaleDefinition = '.$this->formatDef($libPath.'/d3-time-format').';';
        $js .= 'window.d3Formats = '.Json::encode(ChartHelper::formats()).';';

        $view->registerJs($js, View::POS_BEGIN);
    }

    /**
     * Returns the closest-matching D3 format definition for the current language.
     *
     * @param string $dir the path to the directory containing the format files
     * @return string the JSON-encoded format definition
     */
    public function formatDef(string $dir): string
    {
        if (($def = $this->_def($dir, Craft::$app->language)) !== null) {
            return $def;
        }

        // Find the first file in the directory that starts with the language ID
        $language = Craft::$app->getLocale()->getLanguageID();
        $handle = opendir($dir);
        while (($file = readdir($handle)) !== false) {
            if (strncmp($file, $language, 2) === 0) {
                closedir($handle);
                return $this->_def($dir, pathinfo($file, PATHINFO_FILENAME));
            }
        }
        closedir($handle);

        return $this->_def($dir, 'en-US') ?? '{}';
    }

    /**
     * Returns a D3 format definition if it exists.
     *
     * @param string $dir
     * @param string $file
     * @return string|null
     */
    private function _def(string $dir, string $file)
    {
        $path = $dir.DIRECTORY_SEPARATOR.$file.'.json';
        return file_exists($path) ? file_get_contents($path) : null;
    }
}

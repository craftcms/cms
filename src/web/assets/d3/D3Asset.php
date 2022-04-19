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
use craft\i18n\Locale;
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
    public $sourcePath = __DIR__ . '/dist';

    /**
     * @var array The default language format files to use
     */
    private array $_defaultLanguages = [
        'ar' => 'ar-SA',
        'de' => 'de-DE',
        'en' => 'en-US',
        'es' => 'es-ES',
        'fr' => 'fr-FR',
    ];

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->js = [
            'd3.js',
        ];

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view): void
    {
        parent::registerAssetFiles($view);

        // Add locale definition JS variables
        $locale = Craft::$app->getFormattingLocale();
        $formatter = Craft::$app->getFormatter();

        // https://github.com/d3/d3-format#formatLocale
        $localeDef = [
            'decimal' => $locale->getNumberSymbol(Locale::SYMBOL_DECIMAL_SEPARATOR),
            'thousands' => $locale->getNumberSymbol(Locale::SYMBOL_GROUPING_SEPARATOR),
            'grouping' => [3],
            'currency' => $locale->getCurrencySymbol('USD'),
            'numerals' => [
                $formatter->asDecimal(0, 0),
                $formatter->asDecimal(1, 0),
                $formatter->asDecimal(2, 0),
                $formatter->asDecimal(3, 0),
                $formatter->asDecimal(4, 0),
                $formatter->asDecimal(5, 0),
                $formatter->asDecimal(6, 0),
                $formatter->asDecimal(7, 0),
                $formatter->asDecimal(8, 0),
                $formatter->asDecimal(9, 0),
            ],
            'percent' => $locale->getNumberSymbol(Locale::SYMBOL_PERCENT),
            'minus' => $locale->getNumberSymbol(Locale::SYMBOL_MINUS_SIGN),
            'nan' => $locale->getNumberSymbol(Locale::SYMBOL_NAN),
        ];

        $js = 'window.d3FormatLocaleDefinition = ' . Json::encode($localeDef) . ";\n" .
            'window.d3TimeFormatLocaleDefinition = ' . $this->formatDef($this->sourcePath . '/d3-time-format/locale') . ";\n" .
            'window.d3Formats = ' . Json::encode(ChartHelper::formats()) . ';';

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
        $locale = Craft::$app->getFormattingLocale();

        // Do we have locale data for that exact formatting locale?
        if (($def = $this->_def($dir, $locale->id)) !== null) {
            return $def;
        }

        $language = $locale->getLanguageID();

        // Do we have a default for this language ID?
        if (
            isset($this->_defaultLanguages[$language]) &&
            ($def = $this->_def($dir, $this->_defaultLanguages[$language])) !== null
        ) {
            return $def;
        }

        // Find the first file in the directory that starts with the language ID
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
    private function _def(string $dir, string $file): ?string
    {
        $path = $dir . DIRECTORY_SEPARATOR . $file . '.json';
        return file_exists($path) ? file_get_contents($path) : null;
    }
}

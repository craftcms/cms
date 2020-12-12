<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers\utils;

use craft\console\Controller;
use craft\helpers\Console;
use craft\helpers\FileHelper;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use yii\console\ExitCode;

/**
 * Processes SVG files.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.17
 */
class SvgController extends Controller
{
    /**
     * @var bool Whether the SVGs should be sanitized
     */
    public $sanitize = false;

    /**
     * @var bool Whether `id` and other attributes should be namespaced
     */
    public $namespace = false;

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        $actions = parent::options($actionID);
        if ($actionID === 'index') {
            $actions[] = 'sanitize';
            $actions[] = 'namespace';
        }
        return $actions;
    }

    /**
     * Processes SVG files.
     *
     * @param string $pattern The glob pattern to match SVG files.
     * @return int
     */
    public function actionIndex(string $pattern): int
    {
        $paths = glob($pattern);

        foreach ($paths as $path) {
            if (is_dir($path)) {
                $files = FileHelper::findFiles($path, [
                    'filter' => function(string $path): bool {
                        return is_dir($path) || FileHelper::isSvg($path);
                    },
                ]);
            } else {
                $files = [$path];
            }

            foreach ($files as $file) {
                $this->stdout('- Processing ');
                $this->stdout($file, Console::FG_CYAN);
                $this->stdout(' ... ');

                $svg = file_get_contents($file);

                if ($this->sanitize) {
                    $svg = Html::sanitizeSvg($svg);
                }

                // Namespace class names and IDs
                if ($this->namespace) {
                    $ns = StringHelper::randomString(10);
                    $svg = Html::namespaceAttributes($svg, $ns, true);
                }

                FileHelper::writeToFile($file, $svg);
                $this->stdout("done\n", Console::FG_GREEN);
            }
        }

        return ExitCode::OK;
    }
}

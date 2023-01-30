<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\debug;

use Craft;
use yii\debug\Module as DebugModule;
use yii\debug\Panel;
use yii\web\NotFoundHttpException;

/**
 * Debugger panel that collects and displays dumped variables.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
class DumpPanel extends Panel
{
    /**
     * Displays a variable, if the Dump panel is active
     *
     * @param mixed $var The variable to be dumped.
     * @param string $file The source file or template name
     * @param int $line The line number
     */
    public static function dump(mixed $var, string $file, int $line): void
    {
        $debugModule = Craft::$app->getModule('debug');
        if (
            $debugModule instanceof DebugModule &&
            isset($debugModule->panels['dump']) &&
            $debugModule->panels['dump'] instanceof DumpPanel
        ) {
            $dump = Craft::dump($var, return: true);
            $debugModule->panels['dump']->data[] = [$file, $line, $dump];
        }
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'Dumps';
    }

    /**
     * @inheritdoc
     */
    public function getSummary(): string
    {
        return Craft::$app->getView()->render('@app/views/debug/dump/summary', [
            'panel' => $this,
        ]);
    }

    /**
     * @inheritdoc
     * @throws NotFoundHttpException if a `trace` parameter is in the query string, but its value isn’t a valid deprecation warning’s ID
     */
    public function getDetail(): string
    {
        return Craft::$app->getView()->render('@app/views/debug/dump/detail', [
            'panel' => $this,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function save()
    {
        return $this->data ?? [];
    }
}

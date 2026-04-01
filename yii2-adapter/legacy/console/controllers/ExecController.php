<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\console\Controller;
use craft\helpers\Console;
use ParseError;
use yii\console\ExitCode;

/**
 * Executes a PHP statement and outputs the result.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 * @deprecated 6.0.0 use `php artisan tinker` instead.
 */
class ExecController extends Controller
{
    /**
     * @inheritdoc
     */
    public $defaultAction = 'exec';

    /**
     * Executes a PHP statement and outputs the result.
     *
     * @param string $code
     * @return int
     */
    public function actionExec(string $code): int
    {
        ob_start();

        try {
            eval("\$result = $code;");
            $showResult = true;
        } catch (ParseError) {
            eval("$code;");
            $showResult = false;
        }

        $output = ob_get_clean();

        if ($showResult) {
            // Dump the result
            $this->stdout('= ', Console::FG_GREY);
            /** @var mixed $result */
            /** @phpstan-ignore-next-line */
            $dump = Craft::dump($result, return: true);
            $this->stdout(trim(preg_replace('/^/m', '  ', trim($dump))) . "\n\n");
        }

        if ($output !== '') {
            $this->stdout("Output:\n", Console::FG_GREY);
            $this->stdout("$output\n\n");
        }

        return ExitCode::OK;
    }
}

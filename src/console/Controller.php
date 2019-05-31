<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console;


use craft\base\ConsoleControllerTrait;
use \yii\console\Controller as BaseConsoleController;

/**
 * Base console controller
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0.1
 */
class Controller extends BaseConsoleController
{
    use ConsoleControllerTrait;
}

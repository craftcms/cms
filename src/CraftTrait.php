<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

use craft\console\Application as ConsoleApplication;
use craft\web\Application as WebApplication;

/**
 * Helps with IDE auto-completion.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.12.1
 */
trait CraftTrait
{
    /**
     * @var WebApplication|ConsoleApplication|null
     */
    public static WebApplication|ConsoleApplication|null $app = null;
}

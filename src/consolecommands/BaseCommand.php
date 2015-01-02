<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\consolecommands;

/**
 * BaseCommand extends Yii's [[\CConsoleCommand]] and represents an executable console command.
 *
 * It works like [[\CController]] by parsing command line options and dispatching the request to a specific action
 * with appropriate option values.
 *
 * Users call a console command via the following command format:
 *
 * ```bash
 * yiic CommandName ActionName --Option1=Value1 --Option2=Value2 ...
 * ```
 *
 * Child classes mainly needs to implement various action methods whose name must be prefixed with "action". The
 * parameters to an action method are considered as options for that specific action. The action specified as
 * [[defaultAction]] will be invoked when a user does not specify the action name in his command.
 *
 * Options are bound to action parameters via parameter names. For example, the following action method will allow us to
 * run a command with <code>yiic sitemap --type=News</code>:
 *
 * ```php
 * class SitemapCommand extends BaseCommand
 * {
 *     public function actionIndex($type)
 *     {
 *         ....
 *     }
 * }
 * ```
 *
 * The return value of action methods will be used as application exit code if it is an integer value.
 *
 * A Craft plugin can add its own custom commands by adding a 'consolecommands' folder and adding a class that extends
 * BaseCommand.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class BaseCommand extends \CConsoleCommand
{

}

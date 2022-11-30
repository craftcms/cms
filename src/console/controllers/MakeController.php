<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use craft\console\actions\make\PluginAction;
use craft\console\Controller;

/**
 * Scaffolds new components for plugins and modules.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
class MakeController extends Controller
{
    /**
     * @inheritdoc
     */
    public function defineActions(): array
    {
        $actions = [
             'plugin' => [
                 'action' => PluginAction::class,
             ],
         ];

        return array_merge($actions, parent::defineActions());
    }
}

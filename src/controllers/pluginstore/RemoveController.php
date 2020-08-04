<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers\pluginstore;

use Craft;
use craft\controllers\BaseUpdaterController;

/**
 * RemoveController handles the plugin removal workflow.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @internal
 */
class RemoveController extends BaseUpdaterController
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        // Only admins can remove plugins
        $this->requireAdmin();

        return true;
    }

    /**
     * @inheritdoc
     */
    protected function pageTitle(): string
    {
        return Craft::t('app', 'Plugin Uninstaller');
    }

    /**
     * @inheritdoc
     */
    protected function initialData(): array
    {
        return [
            'packageName' => strip_tags($this->request->getRequiredBodyParam('packageName')),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function initialState(): array
    {
        // Make sure we can find composer.json
        if (!$this->ensureComposerJson()) {
            return $this->noComposerJsonState();
        }

        return $this->actionState(self::ACTION_COMPOSER_REMOVE);
    }

    /**
     * @inheritdoc
     */
    protected function postComposerInstallState(): array
    {
        return $this->actionState(self::ACTION_FINISH, [
            'status' => Craft::t('app', 'The plugin was removed successfully.'),
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function returnUrl(): string
    {
        return 'settings/plugins';
    }
}

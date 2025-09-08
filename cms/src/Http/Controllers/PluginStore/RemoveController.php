<?php

namespace CraftCms\Cms\Http\Controllers\PluginStore;

use Craft;
use CraftCms\Cms\Http\Controllers\BaseUpdaterController;

/**
 * @internal
 *
 * @since 6.0.0
 */
final class RemoveController extends BaseUpdaterController
{
    /**
     * {@inheritdoc}
     */
    protected function pageTitle(): string
    {
        return Craft::t('app', 'Plugin Uninstaller');
    }

    /**
     * {@inheritdoc}
     */
    protected function initialData(): array
    {
        $data = $this->request->validate([
            'packageName' => ['required', 'string'],
        ]);

        $data['packageName'] = strip_tags($data['packageName']);

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    protected function initialState(bool $force = false): array
    {
        if (! $this->ensureComposerJson()) {
            return $this->noComposerJsonState();
        }

        return $this->actionState(self::ACTION_COMPOSER_REMOVE);
    }

    /**
     * {@inheritdoc}
     */
    protected function postComposerInstallState(): array
    {
        return $this->actionState(self::ACTION_FINISH, [
            'status' => Craft::t('app', 'The plugin was removed successfully.'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function returnUrl(): string
    {
        return 'settings/plugins';
    }
}

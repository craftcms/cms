<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\PluginStore;

use CraftCms\Cms\Http\Controllers\BaseUpdaterController;

use function CraftCms\Cms\t;

/**
 * @internal
 */
final class RemoveController extends BaseUpdaterController
{
    /**
     * {@inheritdoc}
     */
    #[\Override]
    protected function pageTitle(): string
    {
        return t('Plugin Uninstaller');
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
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
    #[\Override]
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
    #[\Override]
    protected function postComposerInstallState(): array
    {
        return $this->actionState(self::ACTION_FINISH, [
            'status' => t('The plugin was removed successfully.'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    protected function returnUrl(): string
    {
        return 'settings/plugins';
    }
}

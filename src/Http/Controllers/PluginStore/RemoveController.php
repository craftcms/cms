<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\PluginStore;

use CraftCms\Cms\Http\Controllers\BaseUpdaterController;
use CraftCms\Cms\Support\Json;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;
use Override;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\t;

/**
 * @internal
 */
class RemoveController extends BaseUpdaterController
{
    /**
     * Renders the Config Sync page via Inertia.
     */
    #[Override]
    public function index(): Response
    {
        $this->data = $this->initialData();
        $state = $this->realInitialState();
        $state['data'] = Crypt::encrypt(Json::encode($this->data));

        return Inertia::render('updater/Index', [
            'title' => $this->pageTitle(),
            'initialState' => $this->clientState($state),
            'returnUrl' => $this->returnUrl(),
        ])->toResponse($this->request);
    }

    #[Override]
    protected function pageTitle(): string
    {
        return t('Plugin Uninstaller');
    }

    #[Override]
    protected function initialData(): array
    {
        $data = $this->request->validate([
            'packageName' => ['required', 'string'],
        ]);

        $data['packageName'] = strip_tags((string) $data['packageName']);

        return $data;
    }

    #[Override]
    protected function initialState(bool $force = false): array
    {
        if (! $this->ensureComposerJson()) {
            return $this->noComposerJsonState();
        }

        return $this->actionState(self::ACTION_COMPOSER_REMOVE);
    }

    #[Override]
    protected function postComposerInstallState(): array
    {
        return $this->actionState(self::ACTION_FINISH, [
            'status' => t('The plugin was removed successfully.'),
        ]);
    }

    #[Override]
    protected function returnUrl(): string
    {
        return cp_url('settings/plugins');
    }
}

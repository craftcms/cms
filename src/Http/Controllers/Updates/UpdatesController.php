<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Updates;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Plugin\Exceptions\InvalidPluginException;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\PHP;
use CraftCms\Cms\Support\URL;
use CraftCms\Cms\Updates\Data\Update;
use CraftCms\Cms\Updates\Data\Updates as UpdatesData;
use CraftCms\Cms\Updates\Enums\UpdateStatus;
use CraftCms\Cms\Updates\Updates;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use function CraftCms\Cms\t;

/**
 * @internal
 */
readonly class UpdatesController
{
    public function __construct(
        private GeneralConfig $generalConfig,
        private Updates $updates,
        private Plugins $plugins,
    ) {}

    public function check(Request $request): JsonResponse
    {
        if ($request->boolean('onlyIfCached') && ! $this->updates->isUpdateInfoCached()) {
            return new JsonResponse(['cached' => false]);
        }

        $updates = $this->updates->getUpdates($request->boolean('forceRefresh'));

        return $this->updatesResponse($updates, $request->boolean('includeDetails'));
    }

    public function cache(Request $request): JsonResponse
    {
        $updateData = $request->input('updates');

        $updates = $this->updates->cacheUpdates(UpdatesData::fromArray($updateData));

        return $this->updatesResponse($updates, $request->boolean('includeDetails'));
    }

    private function updatesResponse(UpdatesData $updates, bool $includeDetails): JsonResponse
    {
        $allowUpdates = (
            $this->generalConfig->allowUpdates &&
            $this->generalConfig->allowAdminChanges &&
            Auth::user()->can('performUpdates')
        );

        $res = [
            'total' => $updates->getTotal(),
            'critical' => $updates->hasCritical(),
            'allowUpdates' => $allowUpdates,
        ];

        if ($includeDetails) {
            $res['updates'] = [
                'cms' => $this->transformUpdate($allowUpdates, $updates->cms, 'craft', 'Craft CMS'),
                'plugins' => [],
            ];

            foreach ($updates->plugins as $pluginHandle => $pluginUpdate) {
                try {
                    $pluginInfo = $this->plugins->getPluginInfo($pluginHandle);
                } catch (InvalidPluginException) {
                    continue;
                }
                $res['updates']['plugins'][] = $this->transformUpdate($allowUpdates, $pluginUpdate, $pluginHandle, $pluginInfo['name']);
            }
        }

        return new JsonResponse($res);
    }

    private function transformUpdate(bool $allowUpdates, Update $update, string $handle, string $name): array
    {
        $arr = $update->toArray();
        $arr['status'] = $update->status->value;
        $arr['handle'] = $handle;
        $arr['name'] = $name;
        $arr['latestVersion'] = $update->latest()->version ?? null;

        // Make sure that the platform & composer.json PHP version are compatible
        if (
            $update->phpConstraint &&
            $phpConstraintError = PHP::checkConstraint($update->phpConstraint, true)
        ) {
            $arr['status'] = 'phpIssue';
            $arr['statusText'] = $phpConstraintError;
            $arr['ctaUrl'] = false;

            return $arr;
        }

        if ($update->status === UpdateStatus::EXPIRED) {
            $arr['statusText'] = t('<strong>Your license has expired!</strong> Renew your {name} license for another year of amazing updates.', [
                'name' => $name,
            ]);

            $arr['ctaText'] = t('Renew for {price}', [
                'price' => I18N::getFormatter()->asCurrency($update->renewalPrice, $update->renewalCurrency),
            ]);

            $arr['ctaUrl'] = URL::url($update->renewalUrl);

            if ($allowUpdates && Edition::canTest()) {
                $arr['altCtaText'] = t('Update anyway');
            }

            return $arr;
        }

        if ($allowUpdates) {
            $arr['ctaText'] = t('Update');
        }

        if ($update->abandoned) {
            $arr['statusText'] = Html::tag('strong', t('This plugin is no longer maintained.'));

            if ($update->replacementName) {
                if (Auth::user()?->isAdmin() && $this->generalConfig->allowAdminChanges) {
                    $replacementUrl = URL::url("plugin-store/$update->replacementHandle");
                } else {
                    $replacementUrl = $update->replacementUrl;
                }
                $arr['statusText'] .= ' '.
                    t('The developer recommends using <a href="{url}">{name}</a> instead.', [
                        'url' => $replacementUrl,
                        'name' => $update->replacementName,
                    ]);
            }

            return $arr;
        }

        if ($update->status === UpdateStatus::BREAKPOINT) {
            $arr['statusText'] = t('<strong>You’ve reached a breakpoint!</strong> More updates will become available after you install {update}.', [
                'update' => $name.' '.($update->latest()->version ?? ''),
            ]);
        }

        return $arr;
    }
}

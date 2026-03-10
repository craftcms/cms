<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\View;

use Craft;
use CraftCms\Cms\View\Contracts\CacheCollectorInterface;
use CraftCms\Cms\View\Data\TemplateCacheContext;
use yii\web\AssetBundle;

final class LegacyAssetBundleCollector implements CacheCollectorInterface
{
    public static function key(): string
    {
        return 'legacy-asset-bundles';
    }

    public function begin(TemplateCacheContext $context): void
    {
        if (!$context->resources) {
            return;
        }

        Craft::$app->getView()->startAssetBundleBuffer();
    }

    public function end(TemplateCacheContext $context): array
    {
        if (!$context->resources) {
            return [];
        }

        $bufferedAssetBundles = Craft::$app->getView()->clearAssetBundleBuffer();

        if (!is_array($bufferedAssetBundles)) {
            return [];
        }

        return collect($bufferedAssetBundles)
            ->map(fn(AssetBundle $bundle, string $name) => [$name, $bundle->jsOptions['position'] ?? null])
            ->values()
            ->all();
    }

    public function apply(mixed $payload, TemplateCacheContext $context): void
    {
        if (!$context->resources || !is_array($payload)) {
            return;
        }

        foreach ($payload as [$name, $position]) {
            if (!is_string($name)) {
                continue;
            }

            Craft::$app->getView()->registerAssetBundle($name, $position);
        }
    }
}

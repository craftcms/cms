<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\LegacyAssets;

use CraftCms\Cms\View\HtmlStack;
use CraftCms\Cms\View\RegisteredClientAssets;
use Illuminate\Container\Attributes\Scoped;

/**
 * @deprecated
 *
 * @internal
 */
#[Scoped]
class InternalAssetRegistry
{
    /** @var array<string, true> */
    private array $registeredBundles = [];

    /** @var array<string, true> */
    private array $pendingBundles = [];

    public function __construct(
        private readonly HtmlStack $htmlStack,
        private readonly RegisteredClientAssets $clientAssets,
    ) {
        foreach (session()->pull('__ab', []) as $bundle) {
            $this->register($bundle);
        }
    }

    /**
     * @param  class-string<LegacyAssetInterface>  $bundle
     */
    public function register(string $bundle): void
    {
        if (isset($this->registeredBundles[$bundle]) || isset($this->pendingBundles[$bundle])) {
            return;
        }

        $this->pendingBundles[$bundle] = true;
    }

    public function flash(string $bundle): void
    {
        $bundles = session()->pull('__ab', []);
        $bundles[] = $bundle;
        session()->flash('__ab', $bundles);
    }

    public function flush(): void
    {
        while ($bundle = array_key_first($this->pendingBundles)) {
            $this->registerPendingBundle($bundle);
        }
    }

    /**
     * @param  class-string<LegacyAssetInterface>  $bundle
     */
    private function registerPendingBundle(string $bundle): void
    {
        if (isset($this->registeredBundles[$bundle])) {
            unset($this->pendingBundles[$bundle]);

            return;
        }

        $this->pendingBundles[$bundle] ??= true;

        /** @var LegacyAssetInterface $bundle */
        $bundle = app($bundle);

        foreach ($bundle->depends as $dependency) {
            $this->registerPendingBundle($dependency);
        }

        // Skip bundles the browser reported as already loaded (Craft 5's
        // registered-asset-bundles mechanism); dependencies were still walked
        // above so each one makes its own call on this.
        if (! $this->clientAssets->hasBundle($bundle::class)) {
            $bundle->register($this->htmlStack);
        }

        $this->clientAssets->trackBundle($bundle::class);

        unset($this->pendingBundles[$bundle::class]);
        $this->registeredBundles[$bundle::class] = true;
    }
}

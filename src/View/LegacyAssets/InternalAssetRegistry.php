<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\LegacyAssets;

use CraftCms\Cms\View\HtmlStack;
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
    ) {}

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

        if (! isset($this->pendingBundles[$bundle])) {
            $this->pendingBundles[$bundle] = true;
        }

        /** @var LegacyAssetInterface $bundle */
        $bundle = app($bundle);

        foreach ($bundle->depends as $dependency) {
            $this->registerPendingBundle($dependency);
        }

        $bundle->register($this->htmlStack);

        unset($this->pendingBundles[$bundle::class]);
        $this->registeredBundles[$bundle::class] = true;
    }
}

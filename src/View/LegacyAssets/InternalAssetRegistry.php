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

    public function __construct(
        private readonly HtmlStack $htmlStack,
    ) {}

    /**
     * @param  class-string<LegacyAssetInterface>  $bundle
     */
    public function register(string $bundle): void
    {
        if (isset($this->registeredBundles[$bundle])) {
            return;
        }

        /** @var LegacyAssetInterface $bundle */
        $bundle = app($bundle);

        foreach ($bundle->depends as $dependency) {
            $this->register($dependency);
        }

        $bundle->register($this->htmlStack);

        $this->registeredBundles[$bundle::class] = true;
    }
}

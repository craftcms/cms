<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\LegacyAssets;

use CraftCms\Cms\View\HtmlStack;

interface LegacyAssetInterface
{
    /** @var class-string<LegacyAssetInterface>[] */
    public array $depends { get; set; }

    public function register(HtmlStack $htmlStack): void;
}

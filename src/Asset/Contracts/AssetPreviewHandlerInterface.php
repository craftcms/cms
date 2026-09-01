<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Contracts;

use CraftCms\Cms\Asset\Exceptions\AssetNotPreviewableException;

interface AssetPreviewHandlerInterface
{
    /**
     * Returns the asset preview HTML.
     *
     * @param  array<string, bool|float|int|string|null>  $variables  Additional variables to pass to the template.
     * @return string The preview modal HTML
     *
     * @throws AssetNotPreviewableException if the asset can't be previewed
     */
    public function getPreviewHtml(array $variables = []): string;
}

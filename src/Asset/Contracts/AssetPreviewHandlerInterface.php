<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Contracts;

use yii\base\NotSupportedException;

interface AssetPreviewHandlerInterface
{
    /**
     * Returns the asset preview HTML.
     *
     * @param  array  $variables  Additional variables to pass to the template.
     * @return string The preview modal HTML
     *
     * @throws NotSupportedException if the asset can’t be previewed
     */
    public function getPreviewHtml(array $variables = []): string;
}

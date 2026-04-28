<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue\jobs;

use craft\queue\BaseJob;
use CraftCms\Cms\Support\Facades\I18N;

/**
 * GenerateImageTransform job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 4.4.0
 * @deprecated in Craft 6.0.0. Use {@see \CraftCms\Cms\Image\Jobs\GenerateImageTransform} instead.
 */
class GenerateImageTransform extends BaseJob
{
    /**
     * @var int The transform ID
     */
    public int $transformId;

    /**
     * {@inheritdoc}
     */
    public function execute($queue): void
    {
        new \CraftCms\Cms\Image\Jobs\GenerateImageTransform($this->transformId, $this->description)->handle();
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultDescription(): ?string
    {
        return I18N::prep('Generating image transform');
    }
}

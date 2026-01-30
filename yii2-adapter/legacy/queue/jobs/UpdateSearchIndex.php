<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue\jobs;

use craft\base\ElementInterface;
use craft\queue\BaseJob;
use CraftCms\Cms\Support\Facades\I18N;

/**
 * UpdateSearchIndex job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.2.0
 * @deprecated in Craft 6.0.0. Use {@see \CraftCms\Cms\Search\Jobs\UpdateSearchIndex} instead.
 */
class UpdateSearchIndex extends BaseJob
{
    /**
     * @var class-string<ElementInterface> The type of elements to update.
     */
    public string $elementType;

    /**
     * @var int|int[]|null The ID(s) of the element(s) to update
     */
    public array|int|null $elementId = null;

    /**
     * @var int|string|null The site ID of the elements to update, or `'*'` to update all sites
     */
    public string|int|null $siteId = '*';

    /**
     * @var string[]|null The field handles that should be indexed
     *
     * @since 3.4.0
     */
    public ?array $fieldHandles = null;

    /**
     * @var bool Whether to check if the element’s search indexes are queued to be updated before proceeding.
     *
     * @since 5.7.0
     */
    public bool $queued = false;

    /**
     * {@inheritdoc}
     */
    public function execute($queue): void
    {
        new \CraftCms\Cms\Search\Jobs\UpdateSearchIndex(
            elementType: $this->elementType,
            elementId: $this->elementId,
            siteId: $this->siteId,
            fieldHandles: $this->fieldHandles,
            queued: $this->queued,
        )->handle();
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultDescription(): ?string
    {
        return I18N::prep('Updating search indexes');
    }
}

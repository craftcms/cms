<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue\jobs;

use craft\queue\BaseJob;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Support\Facades\I18N;

/**
 * UpdateElementSlugsAndUris job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.0.0
 * @deprecated in Craft 6.0.0. Use {@see \CraftCms\Cms\Element\Jobs\UpdateElementSlugsAndUris} instead.
 */
class UpdateElementSlugsAndUris extends BaseJob
{
    /**
     * @var int|int[]|null The ID(s) of the element(s) to update
     */
    public array|int|null $elementId = null;

    /**
     * @var class-string<ElementInterface> The type of elements to update.
     */
    public string $elementType;

    /**
     * @var int|null The site ID of the elements to update.
     */
    public ?int $siteId = null;

    /**
     * @var bool Whether the elements’ other sites should be updated as well.
     */
    public bool $updateOtherSites = true;

    /**
     * @var bool Whether the elements’ descendants should be updated as well.
     */
    public bool $updateDescendants = true;

    /**
     * {@inheritdoc}
     */
    public function execute($queue): void
    {
        new \CraftCms\Cms\Element\Jobs\UpdateElementSlugsAndUris(
            elementType: $this->elementType,
            elementId: $this->elementId,
            siteId: $this->siteId,
            updateOtherSites: $this->updateOtherSites,
            updateDescendants: $this->updateDescendants,
        )->handle();
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultDescription(): ?string
    {
        return I18N::prep('Updating element slugs and URIs');
    }
}

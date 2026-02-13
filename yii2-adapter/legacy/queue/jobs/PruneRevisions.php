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
 * PruneRevisions job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.5.0
 * @deprecated in Craft 6.0.0. Use {@see \CraftCms\Cms\Element\Jobs\PruneRevisions} instead.
 */
class PruneRevisions extends BaseJob
{
    /**
     * @var class-string<ElementInterface> The type of elements to update.
     */
    public string $elementType;

    /**
     * @var int The ID of the canonical element.
     */
    public int $canonicalId;

    /**
     * @var int The site ID of the source element
     */
    public int $siteId;

    /**
     * @var int|null The maximum number of revisions an element can have
     *
     * @since 3.5.13
     */
    public ?int $maxRevisions = null;

    /**
     * {@inheritdoc}
     */
    public function execute($queue): void
    {
        new \CraftCms\Cms\Element\Jobs\PruneRevisions(
            $this->elementType,
            $this->canonicalId,
            $this->siteId,
            $this->maxRevisions,
        )->handle();
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultDescription(): ?string
    {
        return I18N::prep('Pruning extra revisions');
    }
}

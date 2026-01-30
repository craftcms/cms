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

/**
 * PropagateElements job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.0.13
 * @deprecated in Craft 6.0.0. Use {@see \CraftCms\Cms\Element\Jobs\PropagateElements} instead.
 */
class PropagateElements extends BaseJob
{
    /**
     * @var class-string<ElementInterface> The element type that should be propagated
     */
    public string $elementType;

    /**
     * @var array|null The element criteria that determines which elements should be propagated
     */
    public ?array $criteria = null;

    /**
     * @var int|int[]|null The site ID(s) that the elements should be propagated to
     *
     * If this is `null`, then elements will be propagated to all supported sites, except the one they were queried in.
     */
    public array|int|null $siteId = null;

    /**
     * @var bool Whether this is for a newly-added site.
     *
     * @since 5.6.10
     */
    public bool $isNewSite = false;

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        parent::init();

        if ($this->siteId !== null) {
            $this->siteId = array_map(fn($siteId) => (int) $siteId, (array) $this->siteId);
        }
    }

    public function execute($queue): void
    {
        new \CraftCms\Cms\Element\Jobs\PropagateElements(
            $this->elementType,
            $this->criteria,
            $this->siteId,
            $this->isNewSite,
        )->handle();
    }
}

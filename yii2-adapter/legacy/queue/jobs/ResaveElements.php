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
 * ResaveElements job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.0.0
 * @deprecated in Craft 6.0.0. Use {@see \CraftCms\Cms\Element\Jobs\ResaveElements} instead.
 */
class ResaveElements extends BaseJob
{
    /**
     * @var class-string<ElementInterface> The element type that should be resaved
     */
    public string $elementType;

    /**
     * @var array|null The element criteria that determines which elements should be resaved
     */
    public ?array $criteria = null;

    /**
     * @var bool Whether to update the search indexes for the resaved elements.
     *
     * @since 3.4.2
     */
    public bool $updateSearchIndex = false;

    /**
     * @var string|null An attribute name that should be set for each of the elements. The value will be determined by [[to]].
     *
     * @since 4.2.6
     */
    public ?string $set = null;

    /**
     * @var string|null The value that should be set on the [[set]] attribute.
     *
     * @since 4.2.6
     */
    public ?string $to = null;

    /**
     * @var bool Whether the [[set]] attribute should only be set if it doesn’t have a value.
     *
     * @since 4.2.6
     */
    public bool $ifEmpty = false;

    /**
     * @var bool Whether the [[set]] attribute should only be set if the current value doesn’t validate.
     *
     * @since 5.1.0
     */
    public bool $ifInvalid = false;

    /**
     * @var bool Whether to update the `dateUpdated` timestamp for the elements.
     *
     * @since 4.2.6
     */
    public bool $touch = false;

    public int $batchSize = 100;

    public function execute($queue): void
    {
        new \CraftCms\Cms\Element\Jobs\ResaveElements(
            $this->elementType,
            $this->criteria,
            $this->updateSearchIndex,
            $this->set,
            $this->to,
            $this->ifEmpty,
            $this->ifInvalid,
            $this->touch,
            $this->batchSize,
            $this->description,
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultDescription(): ?string
    {
        return I18N::prep('Resaving {type}', [
            'type' => $this->elementType::pluralLowerDisplayName(),
        ]);
    }
}

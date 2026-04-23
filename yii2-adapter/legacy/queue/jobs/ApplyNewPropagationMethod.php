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

/**
 * ApplyNewPropagationMethod loads all elements that match a given criteria,
 * and resaves them to apply a new propagation method to them, duplicating them for any sites
 * where they would have been deleted in the process.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.4.8
 * @deprecated in Craft 6.0.0. Use {@see \CraftCms\Cms\Element\Jobs\ApplyNewPropagationMethod} instead.
 */
class ApplyNewPropagationMethod extends BaseJob
{
    /**
     * @var class-string<ElementInterface> The element type to use
     */
    public string $elementType;

    /**
     * @var array|null The element criteria that determines which elements the
     *                 new propagation method should be applied to
     */
    public ?array $criteria = null;

    public function execute($queue): void
    {
        new \CraftCms\Cms\Element\Jobs\ApplyNewPropagationMethod(
            $this->elementType,
            $this->criteria,
            $this->description,
        )->handle();
    }
}

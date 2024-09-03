<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\ElementInterface;
use craft\base\Event;
use craft\elements\db\EagerLoadPlan;

/**
 * SetEagerLoadedElementsEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class SetEagerLoadedElementsEvent extends Event
{
    /**
     * @var string The handle that was used to eager-load the elements
     */
    public string $handle;

    /**
     * @param ElementInterface[] $elements The eager-loaded elements
     */
    public array $elements;

    /**
     * @param EagerLoadPlan $plan The eager-loading plan
     * @since 5.0.0
     */
    public EagerLoadPlan $plan;
}

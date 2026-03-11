<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;
use CraftCms\Cms\Element\Element;

/**
 * @event AfterPropagate The event that is triggered after the element is fully saved and propagated to other sites.
 *
 * {@see Element::afterPropagate()}
 */
final class AfterPropagate
{
    public function __construct(
        public ElementInterface $element,
        /** @var bool Whether the element is brand new */
        public bool $isNew,
    ) {}
}

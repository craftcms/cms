<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;
use CraftCms\Cms\Element\Concerns\HasPreviewTargets;

/**
 * @event RegisterPreviewTargets The event that is triggered when registering the element's preview targets.
 *
 * {@see HasPreviewTargets::getPreviewTargets()}
 */
final class RegisterPreviewTargets
{
    /**
     * @param  ElementInterface  $element  The element
     * @param  array  $previewTargets  The registered preview targets
     */
    public function __construct(
        public ElementInterface $element,
        public array $previewTargets = [],
    ) {}
}

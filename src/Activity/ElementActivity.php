<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;

/**
 * @internal
 *
 * @since 6.0.0
 */
class ElementActivity
{
    public static function shouldRecord(ElementInterface $element): bool
    {
        return $element->getIsCanonical() &&
            ! $element->getIsDraft() &&
            ! $element->getIsRevision() &&
            ! $element->updatingFromDerivative &&
            (! $element instanceof NestedElementInterface || $element->getPrimaryOwnerId() === null);
    }

    public static function shouldRecordWrite(ElementInterface $element): bool
    {
        return self::shouldRecord($element) &&
            ! $element->propagating &&
            ! $element->resaving &&
            ! $element->mergingCanonicalChanges;
    }
}

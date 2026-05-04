<?php

declare(strict_types=1);
namespace craft\base;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * NestedElementInterface defines the common interface to be implemented by elements that can be
     * nested within other elements via a custom field.
     *
     * [[NestedElementTrait]] provides a base implementation.
     *
     * @mixin ElementTrait
     * @mixin Component
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 5.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Contracts\NestedElementInterface} instead.
     */
    interface NestedElementInterface extends \CraftCms\Cms\Element\Contracts\NestedElementInterface
    {
    }
}

class_alias(\CraftCms\Cms\Element\Contracts\NestedElementInterface::class, NestedElementInterface::class);

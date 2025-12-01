<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Contracts;

use craft\base\ElementInterface;

/**
 * CrossSiteCopyableFieldInterface defines the common interface to be implemented by field classes
 * that wish to support copying their values between sites in a multisite installation.
 */
interface CrossSiteCopyableFieldInterface
{
    /**
     * Copies the field’s value from one site to another.
     */
    public function copyCrossSiteValue(ElementInterface $from, ElementInterface $to): void;
}

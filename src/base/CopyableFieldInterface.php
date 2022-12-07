<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/**
 * CopyableFieldInterface defines the common interface to be implemented by field classes
 * that wish to support copying their values between sites in a multisite installation.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
interface CopyableFieldInterface
{
    /**
     * Returns whether the field is copyable between sites.
     *
     * @param ElementInterface|null $element
     * @return bool
     */
    public function getIsCopyable(?ElementInterface $element = null): bool;

    /**
     * Copies field’s value from one element to another.
     *
     * @param ElementInterface $from
     * @param ElementInterface $to
     * @since 4.4.0
     */
    public function copyValueBetweenSites(ElementInterface $from, ElementInterface $to): void;
}

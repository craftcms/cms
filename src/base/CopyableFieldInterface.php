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
 * @since 5.5.0
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
     * @return bool
     */
    public function copyValueBetweenSites(ElementInterface $from, ElementInterface $to): bool;
}

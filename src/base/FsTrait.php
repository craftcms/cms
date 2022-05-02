<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/**
 * FsTrait implements the common methods and properties for filesystem classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
trait FsTrait
{
    /**
     * @var string|null Name
     */
    public ?string $name = null;

    /**
     * @var string|null Handle
     */
    public ?string $handle = null;

    /**
     * @var string|null Old handle
     */
    public ?string $oldHandle = null;

    /**
     * @var bool Whether the volume has a public URL
     */
    public bool $hasUrls = false;

    /**
     * @var string|null The volume’s URL
     */
    public ?string $url = null;

    /**
     * @var string|null UID
     */
    public ?string $uid = null;
}

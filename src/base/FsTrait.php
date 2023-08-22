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
     * @var bool Whether the “Files in this filesystem have public URLs” setting should be shown.
     * @since 4.5.0
     */
    protected static bool $showHasUrlSetting = true;

    /**
     * @var bool Whether the “Base URL” setting should be shown.
     *
     * If this is `false`, and the filesystem has a base URL, [[getRootUrl()]] should be implemented directly,
     * rather than storing the base URL on the [[\craft\base\Fs::$url]] property.
     *
     * @since 4.5.0
     */
    protected static bool $showUrlSetting = true;

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

<?php
declare(strict_types=1);
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\errors\FsException;
use craft\errors\FsObjectNotFoundException;
use craft\fs\Local;
use craft\models\FieldLayout;
use craft\models\FsListing;
use Generator;

/**
 * VolumeInterface defines the common interface to be implemented by volume classes.
 * A class implementing this interface should also use [[SavableComponentTrait]] and [[VolumeTrait]].
 *
 * @mixin VolumeTrait
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
interface VolumeInterface extends SavableComponentInterface
{
    /**
     * Returns the volume's field layout, or `null` if it doesn’t have one.
     *
     * @return FieldLayout|null
     * @since 3.5.0
     */
    public function getFieldLayout(): ?FieldLayout;

    /**
     * Returns the URL to the source, if it’s accessible via HTTP traffic.
     *
     * The URL should end in a `/`.
     *
     * @return string|null The root URL, or `null` if there isn’t one
     */
    public function getRootUrl(): ?string;

    /**
     * Return the file system used by the volume.
     *
     * @return FsInterface
     * @since 4.0.0
     */
    public function getFilesystem(): FsInterface;
}

<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use craft\base\Model;
use DateTime;

/**
 * AssetIndexingSession model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class AssetIndexingSession extends Model
{
    /**
     * @var int|null ID
     */
    public ?int $id = null;

    /**
     * @var string Textual list of volumes being indexed
     */
    public string $indexedVolumes;

    /**
     * @var int|null The total amount of entries.
     */
    public ?int $totalEntries = null;

    /**
     * @var int The number of processed entries.
     */
    public int $processedEntries;

    /**
     * @var bool Whether remote images should be cached locally.
     */
    public bool $cacheRemoteImages;

    /**
     * Whether this session runs in CLI.
     *
     * @var bool
     */
    public bool $isCli = false;

    /**
     * @var bool Whether actions is required.
     */
    public bool $actionRequired;

    /**
     * @var DateTime|null Time when indexing session was created.
     */
    public ?DateTime $dateCreated = null;

    /**
     * @var DateTime|null Time when indexing session was last updated.
     */
    public ?DateTime $dateUpdated = null;

    /**
     * @var array The skipped entries.
     */
    public array $skippedEntries = [];

    /**
     * @var array The missing entries.
     */
    public array $missingEntries = [];
}

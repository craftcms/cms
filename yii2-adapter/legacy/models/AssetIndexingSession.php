<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use craft\base\Model;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Translation\Locale;
use DateTime;

/**
 * AssetIndexingSession model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
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
     * @var bool Whether empty folders should be listed for deletion.
     *
     * @since 4.4.0
     */
    public bool $listEmptyFolders;

    /**
     * Whether this session runs in CLI.
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

    /**
     * @var bool Whether to continue processing if the FS root folder is empty.
     *
     * @since 4.4.0
     */
    public bool $processIfRootEmpty = false;

    /**
     * @var bool Whether we should stop processing the session because there was a problem.
     *
     * @since 5.6.0
     */
    public bool $forceStop = false;

    public function toArray(array $fields = [], array $expand = [], $recursive = true): array
    {
        return Arr::merge(parent::toArray($fields, $expand, $recursive), [
            'dateUpdated' => $this->dateUpdated->format(I18N::getLocale()->getDateTimeFormat('medium', Locale::FORMAT_PHP)),
            'indexedVolumes' => Json::decodeIfJson($this->indexedVolumes),
        ]);
    }
}

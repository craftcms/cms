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
     * @var int|null The total amount of entries.
     */
    public ?int $totalEntries;

    /**
     * @var int The number of processed entries.
     */
    public int $processedEntries;

    /**
     * @var int|null If set, refers to the queue job id.
     */
    public ?int $queueId;

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
}

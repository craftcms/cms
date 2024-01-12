<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\records;

use craft\db\ActiveRecord;
use craft\db\Table;

/**
 * Class AssetIndexData record.
 *
 * @property int $id ID
 * @property string $indexedVolumes Textual list of volumes being indexed
 * @property int|null $totalEntries The total amount of entries.
 * @property int|null $processedEntries The number of processed entries.
 * @property bool $cacheRemoteImages Whether remote images should be cached locally.
 * @property bool $listEmptyFolders Whether to list empty folders for deletion.
 * @property bool $isCli Whether indexing is run via CLI.
 * @property bool $actionRequired Whether action is required.
 * @property bool $processIfRootEmpty Whether to continue processing if the FS root folder is empty.
 * @property string $dateUpdated Time when indexing session was last updated.
 * @property string $dateCreated Time when indexing session was last updated.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class AssetIndexingSession extends ActiveRecord
{
    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return Table::ASSETINDEXINGSESSIONS;
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['totalEntries', 'processedEntries'], 'number', 'integerOnly' => true],
        ];
    }
}

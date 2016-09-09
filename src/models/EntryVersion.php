<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\models;

/**
 * Class EntryVersion model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class EntryVersion extends BaseEntryRevisionModel
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function populateModel($model, $config)
    {
        /** @var static $model */
        // Merge the version and entry data
        $entryData = $config['data'];
        $fieldContent = isset($entryData['fields']) ? $entryData['fields'] : null;
        $config['versionId'] = $config['id'];
        $config['id'] = $config['entryId'];
        $config['revisionNotes'] = $config['notes'];
        $title = $entryData['title'];
        unset($config['data'], $entryData['fields'], $config['entryId'], $config['notes'], $entryData['title']);
        $config = array_merge($config, $entryData);

        parent::populateModel($model, $config);

        $model->title = $title;

        if ($fieldContent) {
            $model->setContentFromRevision($fieldContent);
        }

        return $model;
    }

    // Properties
    // =========================================================================

    /**
     * @var integer Version ID
     */
    public $versionId;

    /**
     * @var integer Num
     */
    public $num;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [
                ['id'],
                'number',
                'min' => -2147483648,
                'max' => 2147483647,
                'integerOnly' => true
            ],
            [['siteId'], 'craft\\app\\validators\\SiteId'],
            [['dateCreated'], 'craft\\app\\validators\\DateTime'],
            [['dateUpdated'], 'craft\\app\\validators\\DateTime'],
            [
                ['root'],
                'number',
                'min' => -2147483648,
                'max' => 2147483647,
                'integerOnly' => true
            ],
            [
                ['lft'],
                'number',
                'min' => -2147483648,
                'max' => 2147483647,
                'integerOnly' => true
            ],
            [
                ['rgt'],
                'number',
                'min' => -2147483648,
                'max' => 2147483647,
                'integerOnly' => true
            ],
            [
                ['level'],
                'number',
                'min' => -2147483648,
                'max' => 2147483647,
                'integerOnly' => true
            ],
            [
                ['sectionId'],
                'number',
                'min' => -2147483648,
                'max' => 2147483647,
                'integerOnly' => true
            ],
            [
                ['typeId'],
                'number',
                'min' => -2147483648,
                'max' => 2147483647,
                'integerOnly' => true
            ],
            [
                ['authorId'],
                'number',
                'min' => -2147483648,
                'max' => 2147483647,
                'integerOnly' => true
            ],
            [['postDate'], 'craft\\app\\validators\\DateTime'],
            [['expiryDate'], 'craft\\app\\validators\\DateTime'],
            [
                ['newParentId'],
                'number',
                'min' => -2147483648,
                'max' => 2147483647,
                'integerOnly' => true
            ],
            [
                ['creatorId'],
                'number',
                'min' => -2147483648,
                'max' => 2147483647,
                'integerOnly' => true
            ],
            [
                ['versionId'],
                'number',
                'min' => -2147483648,
                'max' => 2147483647,
                'integerOnly' => true
            ],
            [
                ['num'],
                'number',
                'min' => -2147483648,
                'max' => 2147483647,
                'integerOnly' => true
            ],
            [
                [
                    'id',
                    'enabled',
                    'archived',
                    'siteId',
                    'enabledForSite',
                    'slug',
                    'uri',
                    'dateCreated',
                    'dateUpdated',
                    'root',
                    'lft',
                    'rgt',
                    'level',
                    'sectionId',
                    'typeId',
                    'authorId',
                    'postDate',
                    'expiryDate',
                    'newParentId',
                    'revisionNotes',
                    'creatorId',
                    'versionId',
                    'num'
                ],
                'safe',
                'on' => 'search'
            ],
        ];
    }
}

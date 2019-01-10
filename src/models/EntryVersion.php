<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

/**
 * Class EntryVersion model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class EntryVersion extends BaseEntryRevisionModel
{
    // Properties
    // =========================================================================

    /**
     * @var int|null Version ID
     */
    public $versionId;

    /**
     * @var int|null Num
     */
    public $num;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        if (isset($config['data'])) {
            // Merge the version and entry data
            $entryData = $config['data'];
            $fieldContent = $entryData['fields'] ?? null;
            $config['versionId'] = $config['id'];
            $config['id'] = $config['entryId'];
            $config['revisionNotes'] = $config['notes'];
            $title = $entryData['title'];
            unset($config['data'], $entryData['fields'], $config['entryId'], $config['notes'], $entryData['title'], $entryData['parentId']);
            $config = array_merge($config, $entryData);
        }

        parent::__construct($config);

        if (!empty($title)) {
            $this->title = $title;
        }

        if (!empty($fieldContent)) {
            $this->setContentFromRevision($fieldContent);
        }
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['versionId', 'num'], 'number', 'integerOnly' => true];
        return $rules;
    }
}

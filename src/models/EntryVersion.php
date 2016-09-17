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
        $rules = parent::rules();
        $rules[] = [['versionId', 'num'], 'number', 'integerOnly' => true];
        $rules[] = [['num'], 'required'];

        return $rules;
    }
}

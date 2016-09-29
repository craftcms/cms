<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\models;

use Craft;

/**
 * Class EntryDraft model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class EntryDraft extends BaseEntryRevisionModel
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function populateModel($model, $config)
    {
        /** @var static $model */
        // Merge the draft and entry data
        $entryData = $config['data'];
        $fieldContent = isset($entryData['fields']) ? $entryData['fields'] : null;
        $config['draftId'] = $config['id'];
        $config['id'] = $config['entryId'];
        $config['revisionNotes'] = $config['notes'];
        $title = $entryData['title'];
        unset($config['data'], $entryData['fields'], $config['entryId'], $config['notes'], $entryData['title']);
        $config = array_merge($config, $entryData);

        parent::populateModel($model, $config);

        // Use the live content as a starting point
        Craft::$app->getContent()->populateElementContent($model);

        if ($title) {
            $model->title = $title;
        }

        if ($fieldContent) {
            $model->setContentFromRevision($fieldContent);
        }
    }

    // Properties
    // =========================================================================

    /**
     * @var integer Draft ID
     */
    public $draftId;

    /**
     * @var string Name
     */
    public $name;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['draftId'], 'number', 'integerOnly' => true];
        $rules[] = [['name'], 'required'];

        return $rules;
    }
}

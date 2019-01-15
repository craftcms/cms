<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use yii\base\InvalidConfigException;

/**
 * Class EntryDraft model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class EntryDraft extends BaseEntryRevisionModel
{
    // Properties
    // =========================================================================

    /**
     * @var int|null Draft ID
     */
    public $draftId;

    /**
     * @var string|null Name
     */
    public $name;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        if (isset($config['data'])) {
            // Merge the draft and entry data
            $entryData = $config['data'];
            $fieldContent = $entryData['fields'] ?? null;
            $config['draftId'] = $config['id'];
            $config['id'] = $config['entryId'];
            $config['revisionNotes'] = $config['notes'];
            $title = $entryData['title'];
            unset($config['data'], $entryData['fields'], $config['entryId'], $config['notes'], $entryData['title']);
            $config = array_merge($config, $entryData);
        }

        parent::__construct($config);

        try {
            $this->getType();
        } catch (InvalidConfigException $e) {
            // We must be missing our typeId or it's invalid.
            $entryTypes = $this->getSection()->getEntryTypes();
            $entryType = reset($entryTypes);
            $this->typeId = $entryType->id;
        }

        // Use the live content as a starting point
        Craft::$app->getContent()->populateElementContent($this);

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
        $rules[] = [['draftId'], 'number', 'integerOnly' => true];
        return $rules;
    }
}

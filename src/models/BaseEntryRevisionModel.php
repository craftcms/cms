<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\base\Field;
use craft\app\helpers\ElementHelper;
use craft\app\elements\Entry;
use craft\app\elements\User;

/**
 * Class BaseEntryRevision model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class BaseEntryRevisionModel extends Entry
{
    // Public Methods
    // =========================================================================

    /**
     * @var integer The revision creator’s user ID
     */
    public $creatorId;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['creatorId'], 'number', 'integerOnly' => true];

        return $rules;
    }

    /**
     * Sets the revision content.
     *
     * @param array $content
     *
     * @return void
     */
    public function setContentFromRevision($content)
    {
        // Swap the field IDs with handles
        $contentByFieldHandles = [];

        foreach ($content as $fieldId => $value) {
            /** @var Field $field */
            $field = Craft::$app->getFields()->getFieldById($fieldId);

            if ($field) {
                $contentByFieldHandles[$field->handle] = $value;
            }
        }

        // Set the values and prep them
        $this->setFieldValuesFromPost($contentByFieldHandles);
    }

    /**
     * Returns the draft's creator.
     *
     * @return User|null
     */
    public function getCreator()
    {
        return Craft::$app->getUsers()->getUserById($this->creatorId);
    }

    /**
     * Returns the element's full URL.
     *
     * @return string
     */
    public function getUrl()
    {
        if ($this->uri === null) {
            ElementHelper::setUniqueUri($this);
        }

        return parent::getUrl();
    }
}

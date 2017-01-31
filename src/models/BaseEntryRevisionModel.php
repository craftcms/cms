<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\models;

use Craft;
use craft\base\Field;
use craft\elements\Entry;
use craft\elements\User;
use craft\helpers\ElementHelper;

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
     * @var int|null The revision creator’s user ID
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
    public function setContentFromRevision(array $content)
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
        $this->setFieldValues($contentByFieldHandles);
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
    public function getUrl(): string
    {
        if ($this->uri === null) {
            ElementHelper::setUniqueUri($this);
        }

        return parent::getUrl();
    }
}

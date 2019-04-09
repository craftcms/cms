<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\Field;
use craft\elements\Entry;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\helpers\ElementHelper;

/**
 * Class BaseEntryRevision model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
    public function attributes()
    {
        $names = parent::attributes();

        // Prevent getUrl() from being called by View::renderObjectTemplate(),
        // which would cause an infinite recursion bug
        ArrayHelper::removeValue($names, 'url');
        ArrayHelper::removeValue($names, 'link');

        return $names;
    }

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
        return $this->creatorId ? Craft::$app->getUsers()->getUserById($this->creatorId) : null;
    }

    /**
     * Returns the element's full URL.
     *
     * @return string|null
     */
    public function getUrl()
    {
        if ($this->uri === null) {
            ElementHelper::setUniqueUri($this);
        }

        return parent::getUrl();
    }
}

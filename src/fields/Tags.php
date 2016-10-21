<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\fields;

use Craft;
use craft\app\base\Element;
use craft\app\elements\db\ElementQueryInterface;
use craft\app\elements\Tag;
use craft\app\models\TagGroup;

/**
 * Tags represents a Tags field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Tags extends BaseRelationField
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName()
    {
        return Craft::t('app', 'Tags');
    }

    /**
     * @inheritdoc
     */
    protected static function elementType()
    {
        return Tag::class;
    }

    /**
     * @inheritdoc
     */
    public static function defaultSelectionLabel()
    {
        return Craft::t('app', 'Add a tag');
    }

    // Properties
    // =========================================================================

    /**
     * Whether the field settings should allow multiple sources to be selected.
     *
     * @var boolean $allowMultipleSources
     */
    protected $allowMultipleSources = false;

    /**
     * Whether to allow the Limit setting.
     *
     * @var boolean $allowLimit
     */
    protected $allowLimit = false;

    /**
     * @var
     */
    private $_tagGroupId;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, $element)
    {
        if (!($value instanceof ElementQueryInterface)) {
            /** @var Element $class */
            $class = static::elementType();
            $value = $class::find()
                ->id(false);
        }

        $tagGroup = $this->_getTagGroup();

        if ($tagGroup) {
            return Craft::$app->getView()->renderTemplate('_components/fieldtypes/Tags/input',
                [
                    'elementType' => static::elementType(),
                    'id' => Craft::$app->getView()->formatInputId($this->handle),
                    'name' => $this->handle,
                    'elements' => $value,
                    'tagGroupId' => $this->_getTagGroupId(),
                    'targetSiteId' => $this->getTargetSiteId($element),
                    'sourceElementId' => (!empty($element) ? $element->id : null),
                    'selectionLabel' => ($this->selectionLabel ? Craft::t('site', $this->selectionLabel) : static::defaultSelectionLabel()),
                ]);
        } else {
            return '<p class="error">'.Craft::t('app', 'This field is not set to a valid source.').'</p>';
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns the tag group associated with this field.
     *
     * @return TagGroup|null
     */
    private function _getTagGroup()
    {
        $tagGroupId = $this->_getTagGroupId();

        if ($tagGroupId) {
            return Craft::$app->getTags()->getTagGroupById($tagGroupId);
        }

        return null;
    }

    /**
     * Returns the tag group ID this field is associated with.
     *
     * @return integer|false
     */
    private function _getTagGroupId()
    {
        if (!isset($this->_tagGroupId)) {
            if (strncmp($this->source, 'taggroup:', 9) == 0) {
                $this->_tagGroupId = (int)mb_substr($this->source, 9);
            } else {
                $this->_tagGroupId = false;
            }
        }

        return $this->_tagGroupId;
    }
}

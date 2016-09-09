<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\base\Model;
use craft\app\behaviors\FieldLayoutTrait;
use craft\app\records\CategoryGroup as CategoryGroupRecord;
use craft\app\validators\Handle;
use craft\app\validators\Unique;

/**
 * CategoryGroup model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 *
 * @property CategoryGroup_SiteSettings[] $siteSettings Site-specific settings
 */
class CategoryGroup extends Model
{
    // Traits
    // =========================================================================

    use FieldLayoutTrait;

    // Properties
    // =========================================================================

    /**
     * @var integer ID
     */
    public $id;

    /**
     * @var integer Structure ID
     */
    public $structureId;

    /**
     * @var integer Field layout ID
     */
    public $fieldLayoutId;

    /**
     * @var string Name
     */
    public $name;

    /**
     * @var string Handle
     */
    public $handle;

    /**
     * @var integer Max levels
     */
    public $maxLevels;

    /**
     * @var
     */
    private $_siteSettings;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'fieldLayout' => [
                'class' => 'craft\app\behaviors\FieldLayoutBehavior',
                'elementType' => 'craft\app\elements\Category'
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'structureId', 'fieldLayoutId', 'maxLevels'], 'number', 'integerOnly' => true],
            [['handle'], Handle::className(), 'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']],
            [['name', 'handle'], Unique::className(), 'targetClass' => CategoryGroupRecord::className()],
            [['name', 'handle', 'siteSettings'], 'required'],
            [['name', 'handle'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function validate($attributeNames = null, $clearErrors = true)
    {
        $validates = parent::validate($attributeNames, $clearErrors);

        if ($attributeNames === null || in_array('siteSettings', $attributeNames)) {
            foreach ($this->getSiteSettings() as $siteSettings) {
                if (!$siteSettings->validate(null, $clearErrors)) {
                    $validates = false;
                }
            }
        }

        return $validates;
    }

    /**
     * Use the translated category group's name as the string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return Craft::t('site', $this->name);
    }

    /**
     * Returns the group's site-specific settings.
     *
     * @return CategoryGroup_SiteSettings[]
     */
    public function getSiteSettings()
    {
        if (!isset($this->_siteSettings)) {
            if ($this->id) {
                $siteSettings = Craft::$app->getCategories()->getGroupSiteSettings($this->id, 'siteId');
            } else {
                $siteSettings = [];
            }

            // Set them with setSiteSettings() so setGroup() gets called on them
            $this->setSiteSettings($siteSettings);
        }

        return $this->_siteSettings;
    }

    /**
     * Sets the group's site-specific settings.
     *
     * @param CategoryGroup_SiteSettings[] $siteSettings
     *
     * @return void
     */
    public function setSiteSettings($siteSettings)
    {
        $this->_siteSettings = $siteSettings;

        foreach ($this->_siteSettings as $settings) {
            $settings->setGroup($this);
        }
    }
}

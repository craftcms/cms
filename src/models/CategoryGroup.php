<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\Model;
use craft\behaviors\FieldLayoutBehavior;
use craft\elements\Category;
use craft\helpers\ArrayHelper;
use craft\records\CategoryGroup as CategoryGroupRecord;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;

/**
 * CategoryGroup model.
 *
 * @property CategoryGroup_SiteSettings[] $siteSettings Site-specific settings
 * @mixin FieldLayoutBehavior
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class CategoryGroup extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var int|null Structure ID
     */
    public $structureId;

    /**
     * @var int|null Field layout ID
     */
    public $fieldLayoutId;

    /**
     * @var string|null Name
     */
    public $name;

    /**
     * @var string|null Handle
     */
    public $handle;

    /**
     * @var int|null Max levels
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
                'class' => FieldLayoutBehavior::class,
                'elementType' => Category::class
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'handle' => Craft::t('app', 'Handle'),
            'name' => Craft::t('app', 'Name'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'structureId', 'fieldLayoutId', 'maxLevels'], 'number', 'integerOnly' => true],
            [['handle'], HandleValidator::class, 'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']],
            [['name', 'handle'], UniqueValidator::class, 'targetClass' => CategoryGroupRecord::class],
            [['name', 'handle', 'siteSettings'], 'required'],
            [['name', 'handle'], 'string', 'max' => 255],
            [['siteSettings'], 'validateSiteSettings'],
        ];
    }

    /**
     * Validates the site settings.
     */
    public function validateSiteSettings()
    {
        foreach ($this->getSiteSettings() as $i => $siteSettings) {
            if (!$siteSettings->validate()) {
                $this->addModelErrors($siteSettings, "siteSettings[{$i}]");
            }
        }
    }

    /**
     * Use the translated category group's name as the string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return Craft::t('site', $this->name) ?: static::class;
    }

    /**
     * Returns the group's site-specific settings.
     *
     * @return CategoryGroup_SiteSettings[]
     */
    public function getSiteSettings(): array
    {
        if ($this->_siteSettings !== null) {
            return $this->_siteSettings;
        }

        if (!$this->id) {
            return [];
        }

        // Set them with setSiteSettings() so setGroup() gets called on them
        $this->setSiteSettings(ArrayHelper::index(Craft::$app->getCategories()->getGroupSiteSettings($this->id), 'siteId'));

        return $this->_siteSettings;
    }

    /**
     * Sets the group's site-specific settings.
     *
     * @param CategoryGroup_SiteSettings[] $siteSettings
     */
    public function setSiteSettings(array $siteSettings)
    {
        $this->_siteSettings = $siteSettings;

        foreach ($this->_siteSettings as $settings) {
            $settings->setGroup($this);
        }
    }
}

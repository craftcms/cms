<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\models;

use Craft;
use craft\base\Model;
use craft\helpers\ArrayHelper;
use craft\records\Section as SectionRecord;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;

/**
 * Section model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 *
 * @property Section_SiteSettings[] $siteSettings Site-specific settings
 */
class Section extends Model
{
    // Constants
    // =========================================================================

    const TYPE_SINGLE = 'single';
    const TYPE_CHANNEL = 'channel';
    const TYPE_STRUCTURE = 'structure';

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
     * @var string|null Name
     */
    public $name;

    /**
     * @var string|null Handle
     */
    public $handle;

    /**
     * @var string|null Type
     */
    public $type;

    /**
     * @var int|null Max levels
     */
    public $maxLevels;

    /**
     * @var bool Enable versioning
     */
    public $enableVersioning = true;

    /**
     * @var
     */
    private $_siteSettings;

    /**
     * @var
     */
    private $_entryTypes;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'structureId', 'maxLevels'], 'number', 'integerOnly' => true],
            [['handle'], HandleValidator::class, 'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']],
            [['type'], 'in', 'range' => ['single', 'channel', 'structure']],
            [['name', 'handle'], UniqueValidator::class, 'targetClass' => SectionRecord::class],
            [['name', 'handle', 'type', 'siteSettings'], 'required'],
            [['name', 'handle'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function validate($attributeNames = null, $clearErrors = true)
    {
        $validates = parent::validate($attributeNames, $clearErrors);

        if ($attributeNames === null || in_array('siteSettings', $attributeNames, true)) {
            foreach ($this->getSiteSettings() as $siteSettings) {
                if (!$siteSettings->validate(null, $clearErrors)) {
                    $validates = false;
                }
            }
        }

        return $validates;
    }

    /**
     * Use the translated section name as the string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return Craft::t('site', $this->name);
    }

    /**
     * Returns the section's site-specific settings.
     *
     * @return Section_SiteSettings[]
     */
    public function getSiteSettings(): array
    {
        if ($this->_siteSettings !== null) {
            return $this->_siteSettings;
        }

        if (!$this->id) {
            return [];
        }

        // Set them with setSiteSettings() so setSection() gets called on them
        $this->setSiteSettings(ArrayHelper::index(Craft::$app->getSections()->getSectionSiteSettings($this->id), 'siteId'));

        return $this->_siteSettings;
    }

    /**
     * Sets the section's site-specific settings.
     *
     * @param Section_SiteSettings[] $siteSettings
     *
     * @return void
     */
    public function setSiteSettings(array $siteSettings)
    {
        $this->_siteSettings = $siteSettings;

        foreach ($this->_siteSettings as $settings) {
            $settings->setSection($this);
        }
    }

    /**
     * Adds site-specific errors to the model.
     *
     * @param array $errors
     * @param int   $siteId
     *
     * @return void
     */
    public function addSiteSettingsErrors(array $errors, int $siteId)
    {
        foreach ($errors as $attribute => $siteErrors) {
            $key = $attribute.'-'.$siteId;
            foreach ($siteErrors as $error) {
                $this->addError($key, $error);
            }
        }
    }

    /**
     * Returns the section's entry types.
     *
     * @return EntryType[]
     */
    public function getEntryTypes(): array
    {
        if ($this->_entryTypes !== null) {
            return $this->_entryTypes;
        }

        if (!$this->id) {
            return [];
        }

        $this->_entryTypes = Craft::$app->getSections()->getEntryTypesBySectionId($this->id);

        return $this->_entryTypes;
    }
}

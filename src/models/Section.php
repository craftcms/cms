<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\Model;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\ArrayHelper;
use craft\records\Section as SectionRecord;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;

/**
 * Section model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 * @property Section_SiteSettings[] $siteSettings Site-specific settings
 * @property EntryType[] $entryTypes Entry types
 * @property bool $hasMultiSiteEntries Whether entries in this section support multiple sites
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
     * @var bool Propagate entries
     */
    public $propagateEntries = true;

    /**
     * @var string|null Section's UID
     */
    public $uid;

    /**
     * @var Section_SiteSettings[]|null
     */
    private $_siteSettings;

    /**
     * @var EntryType[]|null
     */
    private $_entryTypes;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'handle' => Craft::t('app', 'Handle'),
            'name' => Craft::t('app', 'Name'),
            'type' => Craft::t('app', 'Section Type'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['id', 'structureId', 'maxLevels'], 'number', 'integerOnly' => true];
        $rules[] = [['handle'], HandleValidator::class, 'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']];
        $rules[] = [
            ['type'], 'in', 'range' => [
                self::TYPE_SINGLE,
                self::TYPE_CHANNEL,
                self::TYPE_STRUCTURE
            ]
        ];
        $rules[] = [['name', 'handle'], UniqueValidator::class, 'targetClass' => SectionRecord::class];
        $rules[] = [['name', 'handle', 'type', 'siteSettings'], 'required'];
        $rules[] = [['name', 'handle'], 'string', 'max' => 255];
        $rules[] = [['siteSettings'], 'validateSiteSettings'];
        return $rules;
    }

    /**
     * Validates the site settings.
     */
    public function validateSiteSettings()
    {
        // If this is an existing section, make sure they aren't moving it to a
        // completely different set of sites in one fell swoop
        if ($this->id) {
            $currentSiteIds = (new Query())
                ->select(['siteId'])
                ->from([Table::SECTIONS_SITES])
                ->where(['sectionId' => $this->id])
                ->column();

            if (empty(array_intersect($currentSiteIds, array_keys($this->getSiteSettings())))) {
                $this->addError('siteSettings', Craft::t('app', 'At least one currently-enabled site must remain enabled.'));
            }
        }

        foreach ($this->getSiteSettings() as $i => $siteSettings) {
            if (!$siteSettings->validate()) {
                $this->addModelErrors($siteSettings, "siteSettings[{$i}]");
            }
        }
    }

    /**
     * Use the translated section name as the string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return Craft::t('site', $this->name) ?: static::class;
    }

    /**
     * Returns the section's site-specific settings, indexed by site ID.
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

        // Set them with setSiteSettings() so they get indexed by site ID and setSection() gets called on them
        $this->setSiteSettings(Craft::$app->getSections()->getSectionSiteSettings($this->id));

        return $this->_siteSettings;
    }

    /**
     * Sets the section's site-specific settings.
     *
     * @param Section_SiteSettings[] $siteSettings Array of Section_SiteSettings objects.
     */
    public function setSiteSettings(array $siteSettings)
    {
        $this->_siteSettings = ArrayHelper::index($siteSettings, 'siteId');

        foreach ($this->_siteSettings as $settings) {
            $settings->setSection($this);
        }
    }

    /**
     * Returns the site IDs that are enabled for the section.
     *
     * @return int[]
     */
    public function getSiteIds(): array
    {
        return array_keys($this->getSiteSettings());
    }

    /**
     * Adds site-specific errors to the model.
     *
     * @param array $errors
     * @param int $siteId
     */
    public function addSiteSettingsErrors(array $errors, int $siteId)
    {
        foreach ($errors as $attribute => $siteErrors) {
            $key = $attribute . '-' . $siteId;
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

    /**
     * Sets the section's entry types.
     *
     * @param EntryType[] $entryTypes
     */
    public function setEntryTypes(array $entryTypes)
    {
        $this->_entryTypes = $entryTypes;
    }

    /**
     * Returns whether entries in this section support multiple sites.
     *
     * @return bool
     */
    public function getHasMultiSiteEntries(): bool
    {
        return (
            Craft::$app->getIsMultiSite() &&
            count($this->getSiteSettings()) > 1 &&
            $this->propagateEntries
        );
    }
}

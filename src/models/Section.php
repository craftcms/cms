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
use craft\elements\Entry;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\StringHelper;
use craft\records\Section as SectionRecord;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;

/**
 * Section model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @property Section_SiteSettings[] $siteSettings Site-specific settings
 * @property EntryType[] $entryTypes Entry types
 * @property bool $hasMultiSiteEntries Whether entries in this section support multiple sites
 */
class Section extends Model
{
    const TYPE_SINGLE = 'single';
    const TYPE_CHANNEL = 'channel';
    const TYPE_STRUCTURE = 'structure';

    const PROPAGATION_METHOD_NONE = 'none';
    const PROPAGATION_METHOD_SITE_GROUP = 'siteGroup';
    const PROPAGATION_METHOD_LANGUAGE = 'language';
    const PROPAGATION_METHOD_ALL = 'all';
    /**
     * @since 3.5.0
     */
    const PROPAGATION_METHOD_CUSTOM = 'custom';

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
     * @var string Propagation method
     *
     * This will be set to one of the following:
     *
     * - `none` – Only save entries in the site they were created in
     * - `siteGroup` – Save entries to other sites in the same site group
     * - `language` – Save entries to other sites with the same language
     * - `all` – Save entries to all sites enabled for this section
     *
     * @since 3.2.0
     */
    public $propagationMethod = self::PROPAGATION_METHOD_ALL;

    /**
     * @var bool Propagate entries
     * @deprecated in 3.2.0. Use [[$propagationMethod]] instead
     */
    public $propagateEntries = true;

    /**
     * @var array Preview targets
     */
    public $previewTargets = null;

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

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->previewTargets === null) {
            $this->previewTargets = [
                [
                    'label' => Craft::t('app', 'Primary {type} page', [
                        'type' => StringHelper::toLowerCase(Entry::displayName()),
                    ]),
                    'urlFormat' => '{url}',
                ]
            ];
        }

        // todo: remove this in 4.0
        // Set propagateEntries in case anything is still checking it
        $this->propagateEntries = $this->propagationMethod !== self::PROPAGATION_METHOD_NONE;

        parent::init();
    }

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
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['id', 'structureId', 'maxLevels'], 'number', 'integerOnly' => true];
        $rules[] = [['handle'], HandleValidator::class, 'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']];
        $rules[] = [
            ['type'], 'in', 'range' => [
                self::TYPE_SINGLE,
                self::TYPE_CHANNEL,
                self::TYPE_STRUCTURE
            ]
        ];
        $rules[] = [
            ['propagationMethod'], 'in', 'range' => [
                self::PROPAGATION_METHOD_NONE,
                self::PROPAGATION_METHOD_SITE_GROUP,
                self::PROPAGATION_METHOD_LANGUAGE,
                self::PROPAGATION_METHOD_ALL,
                self::PROPAGATION_METHOD_CUSTOM,
            ]
        ];
        $rules[] = [['name', 'handle'], UniqueValidator::class, 'targetClass' => SectionRecord::class];
        $rules[] = [['name', 'handle', 'type', 'propagationMethod', 'siteSettings'], 'required'];
        $rules[] = [['name', 'handle'], 'string', 'max' => 255];
        $rules[] = [['siteSettings'], 'validateSiteSettings'];
        $rules[] = [['previewTargets'], 'validatePreviewTargets'];
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
     * Validates the preview targets.
     */
    public function validatePreviewTargets()
    {
        $hasErrors = false;

        foreach ($this->previewTargets as &$target) {
            $target['label'] = trim($target['label']);
            $target['urlFormat'] = trim($target['urlFormat']);

            if ($target['label'] === '') {
                $target['label'] = ['value' => $target['label'], 'hasErrors' => true];
                $hasErrors = true;
            }
        }
        unset($target);

        if ($hasErrors) {
            $this->addError('previewTargets', Craft::t('app', 'All targets must have a label.'));
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
     * @since 3.1.0
     */
    public function setEntryTypes(array $entryTypes)
    {
        $this->_entryTypes = $entryTypes;
    }

    /**
     * Returns whether entries in this section support multiple sites.
     *
     * @return bool
     * @since 3.0.35
     */
    public function getHasMultiSiteEntries(): bool
    {
        return (
            Craft::$app->getIsMultiSite() &&
            count($this->getSiteSettings()) > 1 &&
            $this->propagationMethod !== self::PROPAGATION_METHOD_NONE
        );
    }

    /**
     * Returns the field layout config for this section.
     *
     * @return array
     * @since 3.5.0
     */
    public function getConfig(): array
    {
        $config = [
            'name' => $this->name,
            'handle' => $this->handle,
            'type' => $this->type,
            'enableVersioning' => (bool)$this->enableVersioning,
            'propagationMethod' => $this->propagationMethod,
            'siteSettings' => [],
        ];

        if (!empty($this->previewTargets)) {
            $config['previewTargets'] = ProjectConfigHelper::packAssociativeArray($this->previewTargets);
        }

        if ($this->type === self::TYPE_STRUCTURE) {
            $config['structure'] = [
                'uid' => $this->structureId ? Db::uidById(Table::STRUCTURES, $this->structureId) : StringHelper::UUID(),
                'maxLevels' => (int)$this->maxLevels ?: null,
            ];
        }

        foreach ($this->getSiteSettings() as $siteId => $siteSettings) {
            $siteUid = Db::uidById(Table::SITES, $siteId);
            $config['siteSettings'][$siteUid] = [
                'enabledByDefault' => (bool)$siteSettings['enabledByDefault'],
                'hasUrls' => (bool)$siteSettings['hasUrls'],
                'uriFormat' => $siteSettings['uriFormat'] ?: null,
                'template' => $siteSettings['template'] ?: null,
            ];
        }

        return $config;
    }
}

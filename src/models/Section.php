<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\Chippable;
use craft\base\CpEditable;
use craft\base\Iconic;
use craft\base\Model;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Entry;
use craft\enums\PropagationMethod;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\records\Section as SectionRecord;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;
use yii\db\Schema;

/**
 * Section model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @property Section_SiteSettings[] $siteSettings Site-specific settings
 * @property EntryType[] $entryTypes Entry types
 * @property bool $hasMultiSiteEntries Whether entries in this section support multiple sites
 */
class Section extends Model implements Chippable, CpEditable, Iconic
{
    public const TYPE_SINGLE = 'single';
    public const TYPE_CHANNEL = 'channel';
    public const TYPE_STRUCTURE = 'structure';

    public const PROPAGATION_METHOD_NONE = 'none';
    public const PROPAGATION_METHOD_SITE_GROUP = 'siteGroup';
    public const PROPAGATION_METHOD_LANGUAGE = 'language';
    public const PROPAGATION_METHOD_ALL = 'all';
    /** @since 3.5.0 */
    public const PROPAGATION_METHOD_CUSTOM = 'custom';

    /** @since 3.7.0 */
    public const DEFAULT_PLACEMENT_BEGINNING = 'beginning';
    /** @since 3.7.0 */
    public const DEFAULT_PLACEMENT_END = 'end';

    /**
     * @inheritdoc
     */
    public static function get(int|string $id): ?static
    {
        /** @phpstan-ignore-next-line */
        return Craft::$app->getEntries()->getSectionById($id);
    }

    /**
     * @var int|null ID
     */
    public ?int $id = null;

    /**
     * @var int|null Structure ID
     */
    public ?int $structureId = null;

    /**
     * @var string|null Name
     */
    public ?string $name = null;

    /**
     * @var string|null Handle
     */
    public ?string $handle = null;

    /**
     * @var self::TYPE_*|null Type
     */
    public ?string $type = null;

    /**
     * @var int|null Max authors
     * @since 5.0.0
     */
    public int|null $maxAuthors = 1;

    /**
     * @var int|null Max levels
     */
    public ?int $maxLevels = null;

    /**
     * @var bool Enable versioning
     */
    public bool $enableVersioning = true;

    /**
     * @var PropagationMethod Propagation method
     *
     * This will be set to one of the following:
     *
     *  - [[PropagationMethod::None]] – Only save entries in the site they were created in
     *  - [[PropagationMethod::SiteGroup]] – Save  entries to other sites in the same site group
     *  - [[PropagationMethod::Language]] – Save entries to other sites with the same language
     *  - [[PropagationMethod::Custom]] – Let each entry choose which sites it should be saved to
     *  - [[PropagationMethod::All]] – Save entries to all sites supported by the owner element
     *
     * @since 3.2.0
     */
    public PropagationMethod $propagationMethod = PropagationMethod::All;

    /**
     * @var string Default placement
     * @phpstan-var self::DEFAULT_PLACEMENT_*
     * @since 3.7.0
     */
    public string $defaultPlacement = self::DEFAULT_PLACEMENT_END;

    /**
     * @var array|null Preview targets
     */
    public ?array $previewTargets = null;

    /**
     * @var string|null Section's UID
     */
    public ?string $uid = null;

    /**
     * @var Section_SiteSettings[]|null
     */
    private ?array $_siteSettings = null;

    /**
     * @var EntryType[]|null
     */
    private ?array $_entryTypes = null;

    /**
     * @see page()
     */
    private string|false $page;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        if (!isset($this->previewTargets)) {
            $this->previewTargets = [
                [
                    'label' => Craft::t('app', 'Primary {type} page', [
                        'type' => Entry::lowerDisplayName(),
                    ]),
                    'urlFormat' => '{url}',
                ],
            ];
        }

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function getUiLabel(): string
    {
        return Craft::t('site', $this->name);
    }

    /**
     * @inheritdoc
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'handle' => Craft::t('app', 'Handle'),
            'name' => Craft::t('app', 'Name'),
            'type' => Craft::t('app', 'Section Type'),
            'entryTypes' => $this->type === self::TYPE_SINGLE
                ? Craft::t('app', 'Entry Type')
                : Craft::t('app', 'Entry Types'),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['id', 'structureId'], 'number', 'integerOnly' => true];
        $rules[] = [['name', 'handle'], 'trim'];
        $rules[] = [
            ['maxLevels', 'maxAuthors'],
            'number',
            'integerOnly' => true,
            'min' => 0,
            'max' => Db::getMaxAllowedValueForNumericColumn(Schema::TYPE_SMALLINT),
        ];
        $rules[] = [['handle'], HandleValidator::class, 'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']];
        $rules[] = [
            ['type'], 'in', 'range' => [
                self::TYPE_SINGLE,
                self::TYPE_CHANNEL,
                self::TYPE_STRUCTURE,
            ],
        ];
        $rules[] = [['handle'], UniqueValidator::class, 'targetClass' => SectionRecord::class];
        $rules[] = [['name', 'handle', 'type', 'entryTypes', 'propagationMethod', 'siteSettings'], 'required'];
        $rules[] = [['name', 'handle'], 'string', 'max' => 255];
        $rules[] = [['siteSettings'], 'validateSiteSettings'];
        $rules[] = [['defaultPlacement'], 'in', 'range' => [self::DEFAULT_PLACEMENT_BEGINNING, self::DEFAULT_PLACEMENT_END]];
        $rules[] = [['previewTargets'], 'validatePreviewTargets'];
        return $rules;
    }

    /**
     * Validates the site settings.
     */
    public function validateSiteSettings(): void
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
                $this->addModelErrors($siteSettings, "siteSettings[$i]");
            }
        }
    }

    /**
     * Validates the preview targets.
     */
    public function validatePreviewTargets(): void
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
        if (isset($this->_siteSettings)) {
            return $this->_siteSettings;
        }

        if (!$this->id) {
            return [];
        }

        // Set them with setSiteSettings() so they get indexed by site ID and setSection() gets called on them
        $this->setSiteSettings(Craft::$app->getEntries()->getSectionSiteSettings($this->id));

        return $this->_siteSettings;
    }

    /**
     * Sets the section's site-specific settings.
     *
     * @param Section_SiteSettings[] $siteSettings Array of Section_SiteSettings objects.
     */
    public function setSiteSettings(array $siteSettings): void
    {
        $this->_siteSettings = ArrayHelper::index(
            $siteSettings,
            fn(Section_SiteSettings $siteSettings) => $siteSettings->siteId,
        );

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
    public function addSiteSettingsErrors(array $errors, int $siteId): void
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
        if (isset($this->_entryTypes)) {
            return $this->_entryTypes;
        }

        if (!$this->id) {
            return [];
        }

        $this->_entryTypes = Craft::$app->getEntries()->getEntryTypesBySectionId($this->id);

        return $this->_entryTypes;
    }

    /**
     * Sets the section's entry types.
     *
     * @param EntryType[] $entryTypes
     * @since 3.1.0
     */
    public function setEntryTypes(array $entryTypes): void
    {
        $entriesService = Craft::$app->getEntries();
        $this->_entryTypes = array_values(array_filter(array_map(
            fn($entryType) => $entriesService->getEntryType($entryType),
            $entryTypes,
        )));
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
            $this->propagationMethod !== PropagationMethod::None
        );
    }

    /**
     * @inheritdoc
     */
    public function getCpEditUrl(): ?string
    {
        if (!$this->id || !Craft::$app->getUser()->getIsAdmin()) {
            return null;
        }
        return UrlHelper::cpUrl("settings/sections/$this->id");
    }

    /**
     * Returns the section’s control panel index page URI.
     *
     * @return string
     * @since 5.9.0
     */
    public function getCpIndexUri(): string
    {
        $page = $this->getPage();
        return sprintf(
            'content/%s/%s',
            $page ? StringHelper::toKebabCase($page) : 'entries',
            $this->handle,
        );
    }

    /**
     * Returns the page name this section belongs to.
     *
     * @return string|null
     * @since 5.9.0
     */
    public function getPage(): ?string
    {
        if (!isset($this->page)) {
            $sourceKey = $this->type === Section::TYPE_SINGLE ? 'singles' : "section:$this->uid";
            $source = ElementHelper::findSource(Entry::class, $sourceKey, withDisabled: true);
            $this->page = $source['page'] ?? false;
        }

        return $this->page ?: null;
    }

    /**
     * @inheritdoc
     */
    public function getIcon(): ?string
    {
        return 'newspaper';
    }

    /**
     * Returns the section’s config.
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
            'entryTypes' => array_map(fn(EntryType $entryType) => $entryType->getUsageConfig(), $this->getEntryTypes()),
            'enableVersioning' => $this->enableVersioning,
            'maxAuthors' => $this->maxAuthors,
            'propagationMethod' => $this->propagationMethod->value,
            'siteSettings' => [],
            'defaultPlacement' => $this->defaultPlacement ?? self::DEFAULT_PLACEMENT_END,
        ];

        if (!empty($this->previewTargets)) {
            $config['previewTargets'] = array_values($this->previewTargets);
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

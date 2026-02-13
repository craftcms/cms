<?php

declare(strict_types=1);

namespace CraftCms\Cms\Section\Data;

use Closure;
use craft\helpers\ElementHelper;
use craft\helpers\UrlHelper;
use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Component\Contracts\Chippable;
use CraftCms\Cms\Component\Contracts\CpEditable;
use CraftCms\Cms\Component\Contracts\Iconic;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Section\Enums\DefaultPlacement;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Validation\Rules\HandleRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Stringable;

use function CraftCms\Cms\t;

final class Section extends Component implements Chippable, CpEditable, Iconic, Stringable
{
    /**
     * @see getPage()
     */
    private string|false $page;

    public ?int $id = null;

    public ?int $structureId = null;

    public ?string $name = null;

    public ?string $handle = null;

    public ?SectionType $type = null;

    public ?int $maxAuthors = 1;

    public ?int $maxLevels = null;

    public bool $enableVersioning = true;

    public PropagationMethod $propagationMethod = PropagationMethod::All;

    public DefaultPlacement $defaultPlacement = DefaultPlacement::End;

    public ?array $previewTargets = null;

    public ?string $uid = null;

    /** @var SectionSiteSettings[] */
    public array $siteSettings {
        get => $this->getSiteSettings();
        set {
            $this->setSiteSettings($value);
        }
    }

    /** @var SectionSiteSettings[] */
    private ?array $_siteSettings = null;

    public array $entryTypes {
        get => $this->getEntryTypes();
        set {
            $this->setEntryTypes($value);
        }
    }

    private ?array $_entryTypes = null;

    public function __construct(
        array|object $config = [],
    ) {
        parent::__construct($config);

        $this->previewTargets ??= [
            [
                'label' => t('Primary {type} page', [
                    'type' => Entry::lowerDisplayName(),
                ]),
                'urlFormat' => '{url}',
            ],
        ];
    }

    public static function get(int|string $id): ?static
    {
        return Sections::getSectionById($id);
    }

    public function getUiLabel(): string
    {
        return t($this->name, category: 'site');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRules(): array
    {
        return [
            'id' => ['nullable', 'integer'],
            'structureId' => ['nullable', 'integer'],
            'maxLevels' => ['nullable', 'integer', 'min:0', 'max:32767'],
            'maxAuthors' => ['nullable', 'integer', 'min:0', 'max:32767'],
            'name' => ['required', 'string', 'max:255'],
            'handle' => [
                'required',
                'string',
                'max:255',
                new HandleRule(['id', 'dateCreated', 'dateUpdated', 'uid', 'title']),
                Rule::unique(Table::SECTIONS)->ignore($this->id)->withoutTrashed('dateDeleted'),
            ],
            'entryTypes' => ['required'],
            'type' => ['required', Rule::enum(SectionType::class)],
            'defaultPlacement' => ['nullable', Rule::enum(DefaultPlacement::class)],
            'propagationMethod' => ['required', Rule::enum(PropagationMethod::class)],
            'previewTargets' => [
                'nullable',
                'array',
                function (string $attribute, array $value, Closure $fail) {
                    $this->validatePreviewTargets($value, $fail);
                },
            ],
            'siteSettings' => [
                'required',
                'array',
                function (string $attribute, array $value, Closure $fail) {
                    $this->validateSiteSettings($fail);
                },
            ],
        ];
    }

    private function validateSiteSettings(Closure $fail): void
    {
        // If this is an existing section, make sure they aren't moving it to a
        // completely different set of sites in one fell swoop
        if ($this->id) {
            $currentSiteIds = DB::table(Table::SECTIONS_SITES)
                ->where('sectionId', $this->id)
                ->pluck('siteId')
                ->all();

            if (empty(array_intersect($currentSiteIds, array_keys($this->getSiteSettings())))) {
                $fail('siteSettings', t('At least one currently-enabled site must remain enabled.'));
            }
        }

        foreach ($this->getSiteSettings() as $i => $siteSettings) {
            if ($siteSettings->validate()) {
                continue;
            }

            foreach ($siteSettings->errors()->getMessages() as $a => $errors) {
                foreach ($errors as $error) {
                    $this->errors()->add("siteSettings[$i].$a", $error);
                }
            }
        }
    }

    private function validatePreviewTargets(array $value, Closure $fail): void
    {
        $hasErrors = false;

        foreach ($value as &$target) {
            $target['label'] = trim((string) $target['label']);
            $target['urlFormat'] = trim((string) $target['urlFormat']);

            if ($target['label'] === '') {
                $target['label'] = ['value' => $target['label'], 'hasErrors' => true];
                $hasErrors = true;
            }
        }
        unset($target);

        if ($hasErrors) {
            $fail(t('All targets must have a label.'));
        }
    }

    /**
     * Use the translated section name as the string representation.
     */
    public function __toString(): string
    {
        return t($this->name, category: 'site') ?: self::class;
    }

    /**
     * Returns the section's site-specific settings, indexed by site ID.
     *
     * @return \CraftCms\Cms\Section\Data\SectionSiteSettings[]
     */
    public function getSiteSettings(): array
    {
        if (isset($this->_siteSettings)) {
            return $this->_siteSettings;
        }

        if (! $this->id) {
            return [];
        }

        // Set them with setSiteSettings() so they get indexed by site ID and setSection() gets called on them
        $this->setSiteSettings(Sections::getSectionSiteSettings($this->id));

        return $this->_siteSettings;
    }

    /**
     * Sets the section's site-specific settings.
     *
     * @param  \CraftCms\Cms\Section\Data\SectionSiteSettings[]  $siteSettings  Array of SectionSiteSettings objects.
     */
    public function setSiteSettings(array $siteSettings): void
    {
        $this->_siteSettings = Arr::keyBy($siteSettings, 'siteId');

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
     * Returns the section's entry types.
     *
     * @return EntryType[]
     */
    public function getEntryTypes(): array
    {
        if (isset($this->_entryTypes)) {
            return $this->_entryTypes;
        }

        if (! $this->id) {
            return [];
        }

        return $this->_entryTypes = EntryTypes::getEntryTypesBySectionId($this->id)->all();
    }

    /**
     * Sets the section's entry types.
     *
     * @param  EntryType[]  $entryTypes
     *
     * @since 3.1.0
     */
    public function setEntryTypes(array $entryTypes): void
    {
        $this->_entryTypes = array_values(array_filter(array_map(
            fn ($entryType) => EntryTypes::getEntryType($entryType),
            $entryTypes,
        )));
    }

    /**
     * Returns whether entries in this section support multiple sites.
     */
    public function getHasMultiSiteEntries(): bool
    {
        return
            Sites::isMultiSite() &&
            count($this->getSiteSettings()) > 1 &&
            $this->propagationMethod !== PropagationMethod::None;
    }

    public function getCpEditUrl(): ?string
    {
        if (! $this->id || ! Auth::user()?->isAdmin()) {
            return null;
        }

        return UrlHelper::cpUrl("settings/sections/$this->id");
    }

    /**
     * Returns the section’s control panel index page URI.
     */
    public function getCpIndexUri(): string
    {
        $page = $this->getPage();

        return sprintf(
            'content/%s/%s',
            $page ? Str::slug($page) : 'entries',
            $this->type === SectionType::Single ? 'singles' : $this->handle,
        );
    }

    /**
     * Returns the page name this section belongs to.
     */
    public function getPage(): ?string
    {
        if (! isset($this->page)) {
            $sourceKey = $this->type === SectionType::Single ? 'singles' : "section:$this->uid";
            $source = ElementHelper::findSource(Entry::class, $sourceKey);
            $this->page = $source['page'] ?? false;
        }

        return $this->page ?: null;
    }

    public function getIcon(): string
    {
        return 'newspaper';
    }

    public function getConfig(): array
    {
        $config = [
            'name' => $this->name,
            'handle' => $this->handle,
            'type' => $this->type->value,
            'entryTypes' => array_map(fn (EntryType $entryType) => $entryType->getUsageConfig(), $this->getEntryTypes()),
            'enableVersioning' => $this->enableVersioning,
            'maxAuthors' => $this->maxAuthors,
            'propagationMethod' => $this->propagationMethod->value,
            'siteSettings' => [],
            'defaultPlacement' => $this->defaultPlacement->value ?? DefaultPlacement::End->value,
        ];

        if (! empty($this->previewTargets)) {
            $config['previewTargets'] = array_values($this->previewTargets);
        }

        if ($this->type === SectionType::Structure) {
            $config['structure'] = [
                'uid' => $this->structureId ? DB::table(Table::STRUCTURES)->uidById($this->structureId) : Str::uuid()->toString(),
                'maxLevels' => (int) $this->maxLevels ?: null,
            ];
        }

        /**
         * @var \CraftCms\Cms\Section\Data\SectionSiteSettings $siteSettings
         */
        foreach ($this->getSiteSettings() as $siteId => $siteSettings) {
            $siteUid = DB::table(Table::SITES)->uidById($siteId);
            $config['siteSettings'][$siteUid] = [
                'enabledByDefault' => $siteSettings->enabledByDefault,
                'hasUrls' => $siteSettings->hasUrls,
                'uriFormat' => $siteSettings->uriFormat ?: null,
                'template' => $siteSettings->template ?: null,
            ];
        }

        return $config;
    }
}

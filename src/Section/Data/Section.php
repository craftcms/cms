<?php

namespace CraftCms\Cms\Section\Data;

use Closure;
use craft\elements\Entry;
use craft\helpers\UrlHelper;
use CraftCms\Cms\Component\Contracts\Chippable;
use CraftCms\Cms\Component\Contracts\CpEditable;
use CraftCms\Cms\Component\Contracts\Iconic;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\EntryType\Data\EntryType;
use CraftCms\Cms\Section\Enums\DefaultPlacement;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Shared\Rules\HandleRule;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Dto;
use Spatie\LaravelData\Support\Validation\ValidationContext;
use Stringable;

use function CraftCms\Cms\t;

final class Section extends Dto implements Chippable, CpEditable, Iconic, Stringable
{
    public static function get(int|string $id): ?static
    {
        return Sections::getSectionById($id);
    }

    public function __construct(
        public ?int $id = null,
        public ?int $structureId = null,
        public ?string $name = null,
        public ?string $handle = null,
        public ?SectionType $type = null,
        public ?int $maxAuthors = 1,
        public ?int $maxLevels = null,
        public bool $enableVersioning = true,
        public PropagationMethod $propagationMethod = PropagationMethod::All,
        public DefaultPlacement $defaultPlacement = DefaultPlacement::End,
        public ?array $previewTargets = null,
        public ?string $uid = null,
        /** @var SectionSiteSettings[] $siteSettings */
        private ?array $siteSettings = null,
        private ?array $entryTypes = null,
    ) {
        $this->previewTargets ??= [
            [
                'label' => t('Primary {type} page', [
                    'type' => Entry::lowerDisplayName(),
                ]),
                'urlFormat' => '{url}',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getUiLabel(): string
    {
        return t($this->name, category: 'site');
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public static function rules(?ValidationContext $context = null): array
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
                Rule::unique(Table::SECTIONS)->ignore($context->payload['sectionId'] ?? null),
            ],
            'entryTypes' => ['required'],
            'type' => ['required', Rule::enum(SectionType::class)],
            'defaultPlacement' => ['nullable', Rule::enum(DefaultPlacement::class)],
            'propagationMethod' => ['required', Rule::enum(PropagationMethod::class)],
            'sites' => ['required', 'array', function (string $attribute, array $value, Closure $fail) use ($context) {
                if (! isset($context->payload['sectionId'])) {
                    return;
                }

                $siteIds = [];
                foreach (Sites::getAllSites() as $site) {
                    $postedSettings = $value[$site->handle];

                    if (Sites::isMultiSite() && empty($postedSettings['enabled'])) {
                        continue;
                    }

                    $siteIds[] = $site->id;
                }

                $currentSiteIds = DB::table(Table::SECTIONS_SITES)
                    ->where('sectionId', $context->payload['sectionId'])
                    ->pluck('siteId')
                    ->all();

                if (empty(array_intersect($currentSiteIds, $siteIds))) {
                    $fail(t('At least one currently-enabled site must remain enabled.'));
                }
            }],
            'previewTargets' => [
                'nullable',
                'array',
                function (string $attribute, array $value, Closure $fail) {
                    $hasErrors = false;

                    foreach ($value as &$target) {
                        $target['label'] = trim($target['label']);
                        $target['urlFormat'] = trim($target['urlFormat']);

                        if ($target['label'] === '') {
                            $target['label'] = ['value' => $target['label'], 'hasErrors' => true];
                            $hasErrors = true;
                        }
                    }
                    unset($target);

                    if ($hasErrors) {
                        $fail(t('All targets must have a label.'));
                    }
                },
            ],
        ];
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
        if (isset($this->siteSettings)) {
            return $this->siteSettings;
        }

        if (! $this->id) {
            return [];
        }

        // Set them with setSiteSettings() so they get indexed by site ID and setSection() gets called on them
        $this->setSiteSettings(Sections::getSectionSiteSettings($this->id));

        return $this->siteSettings;
    }

    /**
     * Sets the section's site-specific settings.
     *
     * @param  \CraftCms\Cms\Section\Data\SectionSiteSettings[]  $siteSettings  Array of SectionSiteSettings objects.
     */
    public function setSiteSettings(array $siteSettings): void
    {
        $this->siteSettings = Arr::keyBy($siteSettings, 'siteId');

        foreach ($this->siteSettings as $settings) {
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
        if (isset($this->entryTypes)) {
            return $this->entryTypes;
        }

        if (! $this->id) {
            return [];
        }

        return $this->entryTypes = EntryTypes::getEntryTypesBySectionId($this->id)->all();
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
        $this->entryTypes = array_values(array_filter(array_map(
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

    /**
     * {@inheritdoc}
     */
    public function getCpEditUrl(): ?string
    {
        if (! $this->id || ! Auth::user()?->isAdmin()) {
            return null;
        }

        return UrlHelper::cpUrl("settings/sections/$this->id");
    }

    /**
     * {@inheritdoc}
     */
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

<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Entry\EntryTypes;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Section\Data\Section as SectionData;
use CraftCms\Cms\Section\Data\SectionSiteSettings;
use CraftCms\Cms\Section\Enums\DefaultPlacement;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section as SectionModel;
use CraftCms\Cms\Section\Resources\SectionResource;
use CraftCms\Cms\Section\Sections;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Url;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

readonly class SectionsController
{
    use RespondsWithFlash;

    private bool $readOnly;

    public function __construct(
        GeneralConfig $generalConfig,
        private EntryTypes $entryTypes,
    ) {
        $this->readOnly = ! $generalConfig->allowAdminChanges;
    }

    public function index(Request $request, Sections $sections): \Inertia\Response
    {
        $page = $request->integer('page', 1);
        $limit = $request->integer('per_page', 50);
        $searchTerm = $request->input('search');

        $sort = $request->array('sort') ?? [
            ['field' => 'name', 'direction' => 'asc'],
        ];

        $orderBy = match (Arr::get($sort, '0.field')) {
            'handle' => 'handle',
            'type' => 'type',
            default => 'name',
        };

        $sortDir = match (Arr::get($sort, '0.direction')) {
            'desc' => SORT_DESC,
            default => SORT_ASC,
        };

        [$pagination, $tableData] = $sections->getSectionTableData(
            page: $page,
            limit: $limit,
            searchTerm: $searchTerm,
            orderBy: $orderBy,
            sortDir: $sortDir,
        );

        return Inertia::render('SettingsSectionsIndexPage', [
            'crumbs' => fn () => [
                ['label' => t('Settings'), 'url' => Url::cpUrl('settings')],
                ['label' => t('Sections')],
            ],
            'title' => t('Sections'),
            'data' => fn () => $tableData,
            'pagination' => fn () => $pagination,
            'sort' => $sort,
            'searchTerm' => $searchTerm,
            'emptyMessage' => t('No sections exist yet.'),
            'readOnly' => $this->readOnly,
        ]);
    }

    public function create(Sites $sites): CpScreenResponse
    {
        $section = new SectionData([
            'type' => SectionType::Channel,
        ]);

        return new CpScreenResponse()
            ->title(t('Create a new section'))
            ->addCrumb(t('Settings'), 'settings')
            ->addCrumb(t('Sections'), 'settings/sections')
            ->inertiaPage('SettingsSectionsEditPage', $this->sectionProps($section, $sites, brandNew: true));
    }

    public function edit(Sections $sections, Sites $sites, SectionModel $section): CpScreenResponse
    {
        $sectionData = $sections->getSectionById($section->id);
        abort_if(is_null($sectionData), 404, 'Section not found');

        return new CpScreenResponse()
            ->title(trim($sectionData->name) ?: t('Edit Section'))
            ->addCrumb(t('Settings'), 'settings')
            ->addCrumb(t('Sections'), 'settings/sections')
            ->inertiaPage('SettingsSectionsEditPage', $this->sectionProps($sectionData, $sites, brandNew: false));
    }

    private function sectionProps(SectionData $section, Sites $sites, bool $brandNew): array
    {
        $headlessMode = app(GeneralConfig::class)->headlessMode;

        $allSites = $sites->getAllSites();
        $siteSettings = [];

        foreach ($allSites as $site) {
            $settings = $section->siteSettings[$site->id] ?? null;
            $siteSettings[] = [
                'siteId' => $site->id,
                'handle' => $site->handle,
                'name' => $site->getName(),
                'enabled' => $brandNew || $settings !== null,
                'enabledByDefault' => $settings->enabledByDefault ?? true,
                'uriFormat' => $settings?->uriFormat,
                'template' => $settings?->template,
            ];
        }

        return [
            'section' => SectionResource::make($section),
            'homepageUri' => Element::HOMEPAGE_URI,
            'brandNew' => $brandNew,
            'entryTypes' => $this->entryTypes->getAllEntryTypes(),
            'typeOptions' => SectionType::asOptions(),
            'propagationOptions' => PropagationMethod::asOptions(),
            'placementOptions' => DefaultPlacement::asOptions(),
            'templateOptions' => SelectOptions::getTemplateSuggestions(),
            'siteSettings' => $siteSettings,
            'isMultiSite' => $sites->isMultiSite(),
            'headlessMode' => $headlessMode,
            'readOnly' => $this->readOnly,
        ];
    }

    public function store(
        Request $request,
        Sites $sites,
        Sections $sections,
    ): Response {
        $sectionId = $request->input('sectionId');

        if ($sectionId) {
            $sectionId = (int) $sectionId;
            abort_if(is_null($section = $sections->getSectionById($sectionId)), 404, "Invalid section ID: $sectionId");
        } else {
            $section = new SectionData;
        }

        // Main section settings
        $section->name = $request->input('name');
        $section->handle = $request->input('handle');
        $section->type = $request->enum('type', SectionType::class, SectionType::Channel);
        $section->enableVersioning = $request->boolean('enableVersioning', true);
        $maxAuthors = $request->input('maxAuthors');
        $section->maxAuthors = is_numeric($maxAuthors) ? (int) $maxAuthors : null;
        $section->propagationMethod = PropagationMethod::tryFrom($request->input('propagationMethod') ?? '')
            ?? PropagationMethod::All;
        $section->previewTargets = $request->input('previewTargets') ?: [];

        // Structure settings
        if ($section->type === SectionType::Structure) {
            $section->maxLevels = $request->input('maxLevels') ?: null;
            $section->defaultPlacement = $request->input('defaultPlacement') ?? $section->defaultPlacement;
        }

        $section->setEntryTypes($request->array('entryTypes'));

        // Site-specific settings
        $allSiteSettings = [];

        foreach ($sites->getAllSites() as $site) {
            $postedSettings = $request->input('sites')[$site->handle] ?? null;

            if (is_null($postedSettings)) {
                continue;
            }

            // Skip disabled sites if this is a multi-site install
            if ($sites->isMultiSite() && empty($postedSettings['enabled'])) {
                continue;
            }

            $siteSettingsData = [
                'siteId' => $site->id,
            ];

            if ($section->type === SectionType::Single) {
                $siteSettingsData['uriFormat'] = ($postedSettings['singleHomepage'] ?? false)
                    ? Element::HOMEPAGE_URI :
                    ($postedSettings['singleUri'] ?? null);
            } else {
                $siteSettingsData['uriFormat'] = $postedSettings['uriFormat'] ?? null;
                $siteSettingsData['enabledByDefault'] = (bool) ($postedSettings['enabledByDefault'] ?? false);
            }

            if ($siteSettingsData['hasUrls'] = (bool) $siteSettingsData['uriFormat']) {
                $siteSettingsData['template'] = $postedSettings['template'] ?? null;
            }

            $siteSettings = new SectionSiteSettings($siteSettingsData);

            $allSiteSettings[$site->id] = $siteSettings;
        }

        $section->setSiteSettings($allSiteSettings);

        if (! $sections->saveSection($section)) {
            return $this->asModelFailure($section, t('Couldn’t save section.'), 'section');
        }

        return $this->asModelSuccess($section, t('Section saved.'), 'section');
    }

    public function destroy(Request $request, Sections $sections): Response
    {
        $request->validate([
            'id' => ['required', Rule::exists(Table::SECTIONS, 'id')],
        ]);

        $sectionId = $request->integer('id');
        $section = $sections->getSectionById($sectionId);

        $name = $section->name;
        $sections->deleteSectionById($sectionId);

        return $this->asSuccess(t('Section “{name}” deleted.', [
            'name' => $name,
        ]));
    }

    public function tableData(Request $request, Sections $sections): Response
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('per_page', 100);
        $searchTerm = $request->input('search');

        $sort = $request->input('sort');

        $orderBy = match (Arr::get($sort, '0.field')) {
            '__slot:handle' => 'handle',
            'type' => 'type',
            default => 'name',
        };

        $sortDir = match (Arr::get($sort, '0.direction')) {
            'desc' => SORT_DESC,
            default => SORT_ASC,
        };

        [$pagination, $tableData] = $sections->getSectionTableData(
            page: $page,
            limit: $limit,
            searchTerm: $searchTerm,
            orderBy: $orderBy,
            sortDir: $sortDir,
        );

        return $this->asSuccess(data: [
            'pagination' => $pagination,
            'data' => $tableData,
        ]);
    }
}

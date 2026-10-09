<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\Components\CopyAttribute;
use CraftCms\Cms\Cp\Data\ActionItem;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Http\Requests\TableRequest;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Http\ViewModels\SectionEditViewModel;
use CraftCms\Cms\Section\Data\Section as SectionData;
use CraftCms\Cms\Section\Data\SectionSiteSettings;
use CraftCms\Cms\Section\Enums\DefaultPlacement;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section as SectionModel;
use CraftCms\Cms\Section\Sections;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Ui\Nodes\Table as UiTable;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiResolver;
use CraftCms\Cms\Workflow\Models\Workflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
readonly class SectionsController
{
    use RespondsWithFlash;

    private bool $readOnly;

    public function __construct(
        private GeneralConfig $generalConfig,
        private UiResolver $uiResolver,
    ) {
        $this->readOnly = ! $generalConfig->allowAdminChanges;
    }

    public function index(): CpScreenResponse
    {
        $table = UiTable::make('sections')
            ->columns([
                ['key' => 'name', 'label' => t('Name'), 'sortable' => true],
                ['key' => 'handle', 'label' => t('Handle'), 'sortable' => true],
                ['key' => 'type', 'label' => t('Type'), 'sortable' => true],
            ])
            ->dataUrl(route('craft.cp.settings.sections.table-data'))
            ->searchable()
            ->emptyMessage(t('No sections exist yet.'))
            ->unless($this->readOnly, fn (UiTable $table) => $table
                ->createAction(t('New section'), route('craft.cp.settings.sections.create'))
                ->createActionInPageHeader()
                ->deletable());

        return new CpScreenResponse()
            ->title(t('Sections'))
            ->crumbs([
                new ActionItem()->label(t('Settings'))->href(route('craft.cp.settings.index')),
                new ActionItem()->label(t('Sections')),
            ])
            ->ui(Ui::make([$table]));
    }

    public function tableData(TableRequest $request, Sections $sections): JsonResponse
    {
        [$pagination, $tableData] = $sections->getSectionTableData(
            page: $request->page(),
            limit: $request->limit(),
            searchTerm: $request->search(),
            orderBy: $request->orderBy(),
            sortDir: $request->sortDir(),
        );
        $rows = array_map(fn (array $section): array => [
            'id' => $section['id'],
            'name' => [
                'label' => $section['name'],
                'url' => route('craft.cp.settings.sections.edit', ['section' => $section['id']]),
            ],
            'handle' => ['html' => CopyAttribute::make()->value((string) $section['handle'])->toHtml()],
            'type' => $section['type'],
            ...($this->readOnly ? [] : [
                '_deleteUrl' => route('craft.cp.settings.sections.destroy', ['section' => $section['id']]),
                '_deleteConfirmMessage' => t('Are you sure you want to delete “{name}” and all its entries?', ['name' => $section['name']]),
            ]),
        ], $tableData);

        return new JsonResponse([
            'data' => UiTable::prepareRows($rows),
            'pagination' => $pagination,
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
            ->redirectUrl('settings/sections')
            ->addCrumb(t('Sections'), 'settings/sections')
            ->addCrumb(t('Create a new section'))
            ->inertiaPage('settings/sections/Edit', $this->viewModel($section, $sites, brandNew: true));
    }

    public function edit(Sections $sections, Sites $sites, SectionModel $section): CpScreenResponse
    {
        $sectionData = $sections->getSectionById($section->id);
        abort_if(is_null($sectionData), 404, 'Section not found');

        return new CpScreenResponse()
            ->editUrl($sectionData->getCpEditUrl())
            ->title(trim($sectionData->name) ?: t('Edit Section'))
            ->redirectUrl('settings/sections')
            ->addCrumb(t('Settings'), 'settings')
            ->addCrumb(t('Sections'), 'settings/sections')
            ->addCrumb($sectionData->name)
            ->inertiaPage('settings/sections/Edit', $this->viewModel($sectionData, $sites, brandNew: false));
    }

    public function renderUi(Request $request, Sites $sites, Sections $sections): JsonResponse
    {
        $request->validate([
            'values' => ['required', 'array'],
            'values.sectionId' => ['nullable', 'integer', Rule::exists(Table::SECTIONS, 'id')],
            'values.type' => ['required', Rule::enum(SectionType::class)],
            'values.workflowId' => ['nullable', 'integer', Rule::exists(Workflow::class, 'id')],
            'scope' => ['present', 'array', 'size:0'],
        ]);
        // Validated output drops the keys without rules, which is every other value.
        $values = $request->array('values');
        $section = empty($values['sectionId'])
            ? new SectionData(['type' => SectionType::Channel])
            : $sections->getSectionById((int) $values['sectionId']);

        abort_if($section === null, 404, 'Section not found');

        return new JsonResponse([
            'ui' => $this->viewModel(
                $section,
                $sites,
                brandNew: $section->id === null,
                values: $values,
            )->ui(),
        ]);
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
        if (Edition::isAtLeast(Edition::Pro)) {
            $section->workflowId = $request->integer('workflowId') ?: null;
        }
        $section->enableVersioning = $request->boolean('enableVersioning', true);
        $minAuthors = $request->input('minAuthors');
        $maxAuthors = $request->input('maxAuthors');
        $section->minAuthors = is_numeric($minAuthors) ? (int) $minAuthors : 0;
        $section->maxAuthors = is_numeric($maxAuthors) ? (int) $maxAuthors : null;
        $section->propagationMethod = PropagationMethod::tryFrom($request->input('propagationMethod') ?? '')
            ?? PropagationMethod::All;
        $section->previewTargets = $request->input('previewTargets') ?: [];

        // Structure settings
        if ($section->type === SectionType::Structure) {
            $section->maxLevels = $request->integer('maxLevels') ?: null;
            $section->defaultPlacement = $request->enum('defaultPlacement', DefaultPlacement::class, $section->defaultPlacement);
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

            $siteSettingsData['hasUrls'] = (bool) $siteSettingsData['uriFormat'];
            $siteSettings = new SectionSiteSettings($siteSettingsData);

            if ($siteSettings->hasUrls) {
                $siteSettings->applyForm([
                    ...$postedSettings,
                    'uriFormat' => $siteSettings->uriFormat,
                ]);
            }

            $allSiteSettings[$site->id] = $siteSettings;
        }

        $section->setSiteSettings($allSiteSettings);

        if (! $sections->saveSection($section)) {
            $errors = $section->errors()->getMessages();
            $siteErrors = collect($errors)
                ->filter(fn (array $messages, string $path): bool => str_starts_with($path, 'siteSettings'))
                ->flatten()
                ->all();

            if ($siteErrors !== []) {
                $errors['sites'] = $siteErrors;
            }

            throw ValidationException::withMessages($errors);
        }

        return $this->asSuccess(t('Section saved.'));
    }

    public function destroy(Sections $sections, SectionModel $section): Response
    {
        $name = $section->name;
        $sections->deleteSectionById($section->id);

        return $this->asSuccess(t('Section “{name}” deleted.', [
            'name' => $name,
        ]));
    }

    /** @param array<string, mixed>|null $values */
    private function viewModel(
        SectionData $section,
        Sites $sites,
        bool $brandNew,
        ?array $values = null,
    ): SectionEditViewModel {
        return new SectionEditViewModel(
            $section,
            $sites,
            $this->uiResolver,
            $brandNew,
            $this->readOnly,
            $this->generalConfig->headlessMode,
            $values,
        );
    }
}

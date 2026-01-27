<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use craft\helpers\Cp;
use craft\web\assets\editsection\EditSectionAsset;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Data\SectionSiteSettings;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section as SectionModel;
use CraftCms\Cms\Section\Sections;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Arr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

final readonly class SectionsController
{
    use RespondsWithFlash;

    private bool $readOnly;

    public function __construct(
        GeneralConfig $generalConfig,
    ) {
        $this->readOnly = ! $generalConfig->allowAdminChanges;
    }

    public function index(): View
    {
        return view('craftcms::settings.sections._index', [
            'readOnly' => $this->readOnly,
        ]);
    }

    public function create(): CpScreenResponse
    {
        \Craft::$app->getView()->registerAssetBundle(EditSectionAsset::class);

        return new CpScreenResponse()
            ->title(t('Create a new section'))
            ->addCrumb(t('Settings'), 'settings')
            ->addCrumb(t('Sections'), 'settings/sections')
            ->contentTemplate('settings/sections/_edit.twig', [
                'brandNewSection' => true,
                'section' => new Section(
                    type: SectionType::Channel,
                ),
                'typeOptions' => [
                    SectionType::Single->value => SectionType::Single->label(),
                    SectionType::Channel->value => SectionType::Channel->label(),
                    SectionType::Structure->value => SectionType::Structure->label(),
                ],
                'readOnly' => $this->readOnly,
            ])
            ->action('sections/save-section')
            ->redirectUrl('settings/sections')
            ->addAltAction(t('Save and continue editing'), [
                'redirect' => 'settings/sections/{id}',
                'shortcut' => true,
                'retainScroll' => true,
            ]);
    }

    public function edit(Sections $sections, SectionModel $section): CpScreenResponse
    {
        \Craft::$app->getView()->registerAssetBundle(EditSectionAsset::class);

        $sectionData = $sections->getSectionById($section->id);
        abort_if(is_null($sectionData), 404, 'Section not found');

        return new CpScreenResponse()
            ->title(trim($sectionData->name) ?: t('Edit Section'))
            ->addCrumb(t('Settings'), 'settings')
            ->addCrumb(t('Sections'), 'settings/sections')
            ->contentTemplate('settings/sections/_edit.twig', [
                'brandNewSection' => false,
                'sectionId' => $sectionData->id,
                'section' => $sectionData,
                'typeOptions' => [
                    SectionType::Single->value => SectionType::Single->label(),
                    SectionType::Channel->value => SectionType::Channel->label(),
                    SectionType::Structure->value => SectionType::Structure->label(),
                ],
                'readOnly' => $this->readOnly,
            ])
            ->when(
                $this->readOnly,
                function (CpScreenResponse $response) {
                    $response->noticeHtml(Cp::readOnlyNoticeHtml());
                },
                function (CpScreenResponse $response) {
                    $response
                        ->action('sections/save-section')
                        ->redirectUrl('settings/sections')
                        ->addAltAction(t('Save and continue editing'), [
                            'redirect' => 'settings/sections/{id}',
                            'shortcut' => true,
                            'retainScroll' => true,
                        ]);
                },
            );
    }

    public function store(
        Request $request,
        Sites $sites,
        Sections $sections,
        Section $section,
    ): Response {
        $sectionId = $request->integer('sectionId');

        if ($sectionId) {
            $sectionId = (int) $sectionId;
            abort_if(is_null($sectionData = $sections->getSectionById($sectionId)), 404, "Invalid section ID: $sectionId");

            $section->id = $sectionData->id;
            $section->uid = $sectionData->uid;
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
                $siteSettingsData['enabledByDefault'] = (bool) $postedSettings['enabledByDefault'];
            }

            if ($siteSettingsData['hasUrls'] = (bool) $siteSettingsData['uriFormat']) {
                $siteSettingsData['template'] = $postedSettings['template'] ?? null;
            }

            $siteSettings = SectionSiteSettings::from($siteSettingsData);

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

        $sections->deleteSectionById($request->input('id'));

        return $this->asSuccess();
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

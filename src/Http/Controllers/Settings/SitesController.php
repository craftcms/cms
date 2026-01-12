<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use craft\helpers\UrlHelper;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Site\Models\Site as SiteModel;
use CraftCms\Cms\Site\SiteGroups;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Json;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

final readonly class SitesController
{
    use RespondsWithFlash;

    private bool $readOnly;

    public function __construct(
        GeneralConfig $generalConfig,
        private Sites $sites,
        private SiteGroups $siteGroups,
    ) {
        $this->readOnly = ! $generalConfig->allowAdminChanges;
    }

    public function index(Request $request)
    {
        if (($groupId = $request->integer('groupId')) && ! $group = $this->siteGroups->getGroupById($groupId)) {
            abort(404, 'Invalid site group ID: '.$groupId);
        }

        $sites = isset($group)
            ? $this->sites->getSitesByGroupId($groupId)
            : $this->sites->getAllSites()->values();

        $crumbs = [
            ['label' => t('Settings'), 'url' => UrlHelper::cpUrl('settings')],
        ];

        return Inertia::render('SettingsSitesIndex', [
            'crumbs' => $crumbs,
            'nameSuggestions' => Inertia::defer(fn () => SelectOptions::getEnvSuggestions()),
            'group' => $group ?? null,
            'groups' => $this->siteGroups->getAllGroups()->sortBy(['id', 'asc'])->values(),
            'sites' => $sites
                ->sortBy([
                    ['id', 'asc'],
                    ['sortOrder', 'asc'],
                ])->values()->toArray(),
            'readOnly' => $this->readOnly,
        ]);
    }

    public function create(Request $request): View
    {
        $allGroups = $this->siteGroups->getAllGroups();

        abort_if($allGroups->isEmpty(), 500, 'No site groups exist.');
        abort_if(
            $request->has('groupId') && ! $this->siteGroups->getGroupById($request->integer('groupId')),
            404,
            'Site group not found'
        );

        return view('craftcms::settings/sites/_edit', [
            'brandNewSite' => true,
            'title' => t('Create a new site'),
            'crumbs' => [
                [
                    'label' => t('Settings'),
                    'url' => UrlHelper::url('settings'),
                ],
                [
                    'label' => t('Sites'),
                    'url' => UrlHelper::url('settings/sites'),
                ],
            ],
            'site' => new Site(
                name: '',
                handle: '',
                language: $this->sites->getPrimarySite()->getLanguage(false),
            ),
            'groupId' => $request->input('groupId', $allGroups->first()->id),
            'groupOptions' => $allGroups->map(fn ($group) => [
                'label' => $group->name,
                'value' => $group->id,
            ])->all(),
            'readOnly' => $this->readOnly,
        ]);
    }

    public function edit(SiteModel $site, Sites $sitesService)
    {
        $allGroups = $this->siteGroups->getAllGroups();

        abort_if($allGroups->isEmpty(), 500, 'No site groups exist.');

        $siteData = new Site(...$site->except('dateDeleted'));

        return Inertia::render('SettingsSitesEdit', [
            'nameSuggestions' => SelectOptions::getEnvSuggestions(),
            'languageOptions' => SelectOptions::getLanguageOptions(true),
            'booleanEnvOptions' => SelectOptions::getBooleanEnvOptions(),
            'baseUrlSuggestions' => SelectOptions::getEnvSuggestions(true),
            'title' => trim($siteData->getName()) ?: t('Edit Site'),
            'isMultisite' => $sitesService->isMultiSite(),
            'crumbs' => [
                [
                    'label' => t('Settings'),
                    'url' => UrlHelper::url('settings'),
                ],
                [
                    'label' => t('Sites'),
                    'url' => UrlHelper::url('settings/sites'),
                ],
            ],
            'site' => $siteData,
            'groupId' => $siteData->groupId,
            'groupOptions' => $allGroups->map(fn ($group) => [
                'label' => $group->getName(),
                'value' => $group->id,
            ])->all(),
            'readOnly' => $this->readOnly,
        ]);
    }

    public function store(Request $request, Site $siteData): Response
    {
        $request->validate([
            'siteId' => ['nullable', Rule::exists(Table::SITES, 'id')],
            'group' => ['required', 'integer', Rule::exists(Table::SITEGROUPS, 'id')],
        ]);

        $siteId = $request->input('siteId');
        if ($siteId) {
            $siteId = (int) $siteId;
            abort_if(is_null($site = $this->sites->getSiteById($siteId)), 404, "Invalid section ID: $siteId");

            $siteData->id = $site->id;
            $siteData->uid = $site->uid;
        }

        if (! $this->sites->saveSite($siteData)) {
            return back();
        }

        return back()
            ->with('success', t('Site saved.'));
    }

    public function reorder(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);

        if (is_string($ids)) {
            $ids = Json::decode($ids);
        }

        $this->sites->reorderSites($ids);

        return new JsonResponse;
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => ['required', 'integer', Rule::exists(Table::SITES, 'id')],
            'transferContentTo' => ['nullable', 'integer', Rule::exists(Table::SITES, 'id')],
        ]);

        $this->sites->deleteSiteById(
            siteId: (int) $data['id'],
            transferContentTo: $data['transferContentTo'] ?? null,
        );

        return new JsonResponse;
    }
}

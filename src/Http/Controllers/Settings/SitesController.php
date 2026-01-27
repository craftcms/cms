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
use CraftCms\Cms\Support\Str;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

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

    public function index(Request $request, Sites $sitesService): Response
    {
        if (($groupId = $request->integer('groupId')) && ! $group = $this->siteGroups->getGroupById($groupId)) {
            abort(404, 'Invalid site group ID: '.$groupId);
        }

        $sites = isset($group)
            ? $this->sites->getSitesByGroupId($groupId)
            : $this->sites->getAllSites()->values();

        $crumbs = array_filter([
            ['label' => t('Settings'), 'url' => UrlHelper::cpUrl('settings')],
            ['label' => t('Sites'), 'url' => isset($group) ? UrlHelper::cpUrl('settings/sites') : null],
            (isset($group) ? ['label' => $group->getName()] : null),
        ]);

        return Inertia::render('SettingsSitesIndex', [
            'crumbs' => $crumbs,
            'newSiteUrl' => UrlHelper::cpUrl('settings/sites/new'),
            'nameSuggestions' => Inertia::defer(fn () => SelectOptions::getEnvSuggestions()),
            'group' => $group ?? null,
            'groups' => $this->siteGroups->getAllGroups()->sortBy(['id', 'asc'])->values(),
            'sites' => $sites->toArray(),
            'readOnly' => $this->readOnly,
            'transferContentOptions' => Inertia::defer(fn () => $sitesService->getAllSites()->values()),
        ]);
    }

    public function create(Request $request, Sites $sitesService): \Inertia\Response
    {
        $allGroups = $this->siteGroups->getAllGroups();

        abort_if($allGroups->isEmpty(), 500, 'No site groups exist.');
        abort_if(
            $request->has('groupId') && ! $this->siteGroups->getGroupById($request->integer('groupId')),
            404,
            'Site group not found'
        );

        return Inertia::render('SettingsSitesEdit', [
            ...$this->getViewData(),
            'title' => t('Create a new site'),
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
                [
                    'label' => t('Create site'),
                    'url' => UrlHelper::url('settings/sites/new'),
                    'active' => true,
                ],
            ],
            'site' => new Site(
                name: '',
                handle: '',
                language: $this->sites->getPrimarySite()->getLanguage(false),
                groupId: $request->integer('groupId'),
            ),
            'groupId' => $request->input('groupId', $allGroups->first()->id),
            'groupOptions' => $allGroups->map(fn ($group) => [
                'label' => $group->name,
                'value' => $group->id,
            ])->all(),
            'readOnly' => $this->readOnly,
        ]);
    }

    public function edit(SiteModel $site, Sites $sitesService): Response
    {
        $allGroups = $this->siteGroups->getAllGroups();

        abort_if($allGroups->isEmpty(), 500, 'No site groups exist.');

        $siteData = new Site(...$site->except('dateDeleted'));
        $siteGroup = $siteData->getGroup();

        return Inertia::render('SettingsSitesEdit', [
            ...$this->getViewData(),
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
                [
                    'label' => $siteData->getGroup()->getName(),
                    'url' => UrlHelper::url('settings/sites', ['groupId' => $siteGroup->id]),
                ],
                [
                    'label' => $siteData->getName(),
                ],
            ],
            'site' => $siteData,
            'groupId' => $siteData->groupId,
            'groupOptions' => $allGroups->map(fn ($group) => [
                'label' => $group->getName(),
                'value' => $group->id,
            ])->all(),
            'readOnly' => $this->readOnly,
            'transferContentOptions' => Inertia::defer(fn () => $sitesService->getAllSites()->values()),
        ]);
    }

    public function store(Request $request, Site $siteData): Response
    {
        $request->validate([
            'siteId' => ['nullable', Rule::exists(Table::SITES, 'id')],
            'group' => ['required', 'integer', Rule::exists(Table::SITEGROUPS, 'id')],
        ]);

        $isNew = $siteData->id === null;
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

        if ($isNew) {
            return to_route('craft.cp.settings.sites.index')->with('success', t('Site created'));
        }

        return back()
            ->with('success', t('Site saved.'));
    }

    public function reorder(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);

        if (is_string($ids)) {
            $ids = Json::decode($ids);
        }

        $this->sites->reorderSites($ids);

        return back();
    }

    public function destroy(Request $request, SiteModel $siteData): RedirectResponse
    {
        if (! $siteData) {
            abort(404, t('Site not found.'));
        }

        $data = $request->validate([
            'id' => ['required', 'integer', Rule::exists(Table::SITES, 'id')],
            'contentDestination' => ['required', 'in:transfer,delete'],
            'transferContentTo' => Rule::when(
                $request->get('contentDestination') === 'transfer',
                ['required', 'integer', Rule::exists(Table::SITES, 'id')]),
        ]);

        $this->sites->deleteSiteById(
            siteId: (int) $data['id'],
            transferContentTo: (int) $data['transferContentTo'] ?? null,
        );

        return to_route('craft.cp.settings.sites.index')
            ->with('success', t('Site deleted.'));
    }

    /**
     * @return array<string,mixed>
     */
    private function getViewData(): array
    {
        $isValidUrl = fn ($value) => Str::isUrl($value);

        return [
            'languageOptions' => [
                ...SelectOptions::getLanguageOptions(true),
                ...SelectOptions::getLanguageEnvOptions(),
            ],
            'nameSuggestions' => SelectOptions::getEnvSuggestions(),
            'booleanEnvOptions' => [
                [
                    'label' => t('Enabled'),
                    'value' => '1',
                    'data' => [
                        'boolean' => '1',
                    ],
                ],
                [
                    'label' => t('Disabled'),
                    'value' => '0',
                    'data' => [
                        'boolean' => '0',
                    ],
                ],
                ...SelectOptions::getBooleanEnvOptions(),
            ],
            'baseUrlSuggestions' => SelectOptions::getEnvSuggestions(true, $isValidUrl),
        ];
    }
}

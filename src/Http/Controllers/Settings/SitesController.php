<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

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
use CraftCms\Cms\Support\Url;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

use function CraftCms\Cms\t;

readonly class SitesController
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
            ['label' => t('Settings'), 'url' => Url::cpUrl('settings')],
            ['label' => t('Sites'), 'url' => isset($group) ? Url::cpUrl('settings/sites') : null],
            (isset($group) ? ['label' => $group->getName()] : null),
        ]);

        return Inertia::render('SettingsSitesIndex', [
            'crumbs' => $crumbs,
            'newSiteUrl' => Url::cpUrl('settings/sites/new'),
            'nameSuggestions' => Inertia::defer(fn () => SelectOptions::getEnvSuggestions()),
            'group' => $group ?? null,
            'groups' => $this->siteGroups->getAllGroups()->sortBy(['id', 'asc'])->values(),
            'sites' => $sites->toArray(),
            'readOnly' => $this->readOnly,
            'transferContentOptions' => Inertia::defer(fn () => $sitesService->getAllSites()->values()),
        ]);
    }

    public function create(Request $request, Sites $sitesService): Response
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
                    'url' => Url::url('settings'),
                ],
                [
                    'label' => t('Sites'),
                    'url' => Url::url('settings/sites'),
                ],
                [
                    'label' => t('Create site'),
                    'url' => Url::url('settings/sites/new'),
                    'active' => true,
                ],
            ],
            'site' => new Site([
                'name' => '',
                'handle' => '',
                'language' => $this->sites->getPrimarySite()->getLanguage(false),
                'groupId' => $request->integer('groupId'),
            ]),
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

        $siteData = new Site($site->except('dateDeleted'));
        $siteGroup = $siteData->getGroup();

        return Inertia::render('SettingsSitesEdit', [
            ...$this->getViewData(),
            'title' => trim($siteData->getName()) ?: t('Edit Site'),
            'isMultisite' => $sitesService->isMultiSite(),
            'crumbs' => [
                [
                    'label' => t('Settings'),
                    'url' => Url::url('settings'),
                ],
                [
                    'label' => t('Sites'),
                    'url' => Url::url('settings/sites'),
                ],
                [
                    'label' => $siteData->getGroup()->getName(),
                    'url' => Url::url('settings/sites', ['groupId' => $siteGroup->id]),
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

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'siteId' => ['nullable', Rule::exists(Table::SITES, 'id')],
            'group' => ['required', 'integer', Rule::exists(Table::SITEGROUPS, 'id')],
        ]);

        $siteId = $request->input('siteId');
        $isNew = false;
        if ($siteId) {
            abort_if(is_null($site = $this->sites->getSiteById((int) $siteId)), 404, "Invalid site ID: $siteId");
        } else {
            $site = new Site;
            $isNew = true;
        }

        $site->groupId = $request->has('group') ? $request->integer('group') : null;
        $site->name = $request->input('name');
        $site->handle = $request->input('handle');
        $site->language = $request->input('language');
        $site->primary = $request->boolean('primary');
        $site->enabled = $site->primary ? true : $request->input('enabled', true);
        $site->hasUrls = $request->boolean('hasUrls');
        $site->baseUrl = $site->hasUrls ? $request->input('baseUrl') : null;

        if (! $this->sites->saveSite($site)) {
            throw ValidationException::withMessages($site->errors()->getMessages());
        }

        if ($isNew) {
            return to_route('craft.cp.settings.sites.index')->with('success', t('Site created'));
        }

        return back()->with('success', t('Site saved.'));
    }

    public function reorder(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);

        if (is_string($ids)) {
            $ids = Json::decode($ids);
        }

        $this->sites->reorderSites($ids);

        return back()->with('success', t('New order saved.'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id' => ['required', 'integer', Rule::exists(Table::SITES, 'id')],
            'contentDestination' => ['required', 'in:transfer,delete'],
            'transferContentTo' => Rule::when(
                $request->get('contentDestination') === 'transfer',
                ['required', 'integer', Rule::exists(Table::SITES, 'id')]),
        ]);

        $this->sites->deleteSiteById(
            siteId: (int) $data['id'],
            // PHPStan doesn't seem to understand the `Rule::when()` rule above
            // @phpstan-ignore nullCoalesce.expr
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

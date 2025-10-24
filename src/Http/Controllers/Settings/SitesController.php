<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use craft\helpers\UrlHelper;
use craft\web\assets\sites\SitesAsset;
use CraftCms\Cms\Config\GeneralConfig;
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
            : $this->sites->getAllSites();

        $crumbs = [
            ['label' => t('Settings'), 'url' => UrlHelper::cpUrl('settings')],
        ];

        $view = \Craft::$app->getView();
        $view->registerAssetBundle(SitesAsset::class);
        $view->registerTranslations('app', [
            'Could not create the group:',
            'Group renamed.',
            'Could not rename the group:',
            'What do you want to name the group?',
            'Are you sure you want to delete this group?',
            'What do you want to do with any content that is only available in {language}?',
            'Transfer it to:',
            'Delete it',
            'Delete {site}',
        ]);

        return view('craftcms::settings/sites/index', [
            'crumbs' => $crumbs,
            'group' => $group ?? null,
            'sites' => $sites,
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
            'groupId' => $request->get('groupId', $allGroups->first()->id),
            'groupOptions' => $allGroups->map(fn ($group) => [
                'label' => $group->name,
                'value' => $group->id,
            ])->toArray(),
            'readOnly' => $this->readOnly,
        ]);
    }

    public function edit(SiteModel $site): View
    {
        $allGroups = $this->siteGroups->getAllGroups();

        abort_if($allGroups->isEmpty(), 500, 'No site groups exist.');

        $siteData = new Site(...$site->except('dateDeleted'));

        return view('craftcms::settings/sites/_edit', [
            'brandNewSite' => false,
            'title' => trim($siteData->getName()) ?: t('Edit Site'),
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
                'label' => $group->name,
                'value' => $group->id,
            ])->toArray(),
            'readOnly' => $this->readOnly,
        ]);
    }

    public function store(Request $request, Site $siteData): Response
    {
        $request->validate([
            'siteId' => ['nullable', Rule::exists(Table::SITES, 'id')],
            'group' => ['required', 'integer', Rule::exists(Table::SITEGROUPS, 'id')],
        ]);

        $siteData->id = $request->get('siteId');

        if (! $this->sites->saveSite($siteData)) {
            return $this->asModelFailure($siteData, t('Couldn’t save the site.'));
        }

        return $this->asModelSuccess($siteData, t('Site saved.'));
    }

    public function reorder(Request $request): JsonResponse
    {
        $ids = $request->get('ids', []);

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
            siteId: $data['id'],
            transferContentTo: $data['transferContentTo'] ?? null,
        );

        return new JsonResponse;
    }
}

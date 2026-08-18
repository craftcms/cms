<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\Data\NavItem;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\Combobox;
use CraftCms\Cms\Form\Controls\Handle;
use CraftCms\Cms\Form\Controls\Lightswitch;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Form\Nodes\HiddenField;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Site\Models\Site as SiteModel;
use CraftCms\Cms\Site\SiteGroups;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Url;
use Illuminate\Http\JsonResponse;
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
        private FormResolver $formResolver,
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
        $groups = $this->siteGroups->getAllGroups()->sortBy(['id', 'asc'])->values();

        $crumbs = array_filter([
            ['label' => t('Settings'), 'url' => Url::cpUrl('settings')],
            ['label' => t('Sites'), 'url' => isset($group) ? Url::cpUrl('settings/sites') : null],
            (isset($group) ? ['label' => $group->getName()] : null),
        ]);

        return Inertia::render('settings/sites/Index', [
            'title' => isset($group) ? $group->getName() : t('Sites'),
            'crumbs' => $crumbs,
            'newSiteUrl' => Url::cpUrl('settings/sites/new'),
            'nameSuggestions' => Inertia::defer(fn () => SelectOptions::getEnvSuggestions()),
            'group' => $group ?? null,
            'groups' => $groups,
            'subnav' => [
                new NavItem()->label(t('All Sites'))->url(Url::cpUrl('settings/sites'))->selected(! isset($group)),
                ...$groups->map(fn ($siteGroup) => new NavItem()
                    ->label($siteGroup->name)
                    ->url(Url::cpUrl('settings/sites', ['groupId' => $siteGroup->id]))
                    ->selected(isset($group) && $siteGroup->id === $group->id)
                )->all(),
            ],
            'sites' => $sites->toArray(),
            'readOnly' => $this->readOnly,
            'transferContentOptions' => Inertia::defer(fn () => $sitesService->getAllSites()->values()),
        ]);
    }

    public function create(Request $request): CpScreenResponse
    {
        $allGroups = $this->siteGroups->getAllGroups();

        abort_if($allGroups->isEmpty(), 500, 'No site groups exist.');
        abort_if(
            $request->has('groupId') && ! $this->siteGroups->getGroupById($request->integer('groupId')),
            404,
            'Site group not found'
        );

        $groupId = $request->integer('groupId') ?: $allGroups->first()->id;
        $site = new Site([
            'name' => '',
            'handle' => '',
            'language' => $this->sites->getPrimarySite()->getLanguage(false),
            'groupId' => $groupId,
        ]);

        return new CpScreenResponse()
            ->title(t('Create a new site'))
            ->redirectUrl('settings/sites')
            ->crumbs([
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
            ])
            ->inertiaPage('settings/sites/Edit', [
                ...$this->formProps($site),
                'site' => $site,
            ]);
    }

    public function edit(SiteModel $site, Sites $sitesService): CpScreenResponse
    {
        $allGroups = $this->siteGroups->getAllGroups();

        abort_if($allGroups->isEmpty(), 500, 'No site groups exist.');

        $siteData = new Site($site->except('dateDeleted'));
        $siteGroup = $siteData->getGroup();

        return new CpScreenResponse()
            ->title(trim($siteData->getName()) ?: t('Edit Site'))
            ->redirectUrl('settings/sites')
            ->crumbs([
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

            ])
            ->redirectUrl('settings/sites')
            ->inertiaPage('settings/sites/Edit', [
                ...$this->formProps($siteData),
                'site' => $siteData,
                'transferContentOptions' => Inertia::defer(fn () => $sitesService->getAllSites()->values()),
            ]);
    }

    public function renderForm(Request $request): JsonResponse
    {
        $request->validate([
            'values' => ['required', 'array'],
            'values.siteId' => ['nullable', 'integer', Rule::exists(Table::SITES, 'id')],
            'scope' => ['present', 'array', 'size:0'],
        ]);

        return new JsonResponse(['form' => $this->siteForm($request->array('values'))]);
    }

    public function store(Request $request): \Symfony\Component\HttpFoundation\Response
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

        return $this->asSuccess(t('Site saved.'));
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

    /** @return array<string, mixed> */
    private function formProps(Site $site): array
    {
        return [
            'form' => $this->siteForm($this->siteValues($site)),
            'submit' => [
                'method' => 'post',
                'url' => action([self::class, 'store']),
            ],
            'refreshUrl' => action([self::class, 'renderForm']),
        ];
    }

    /** @param array<string, mixed> $values */
    private function siteForm(array $values): FormPayload
    {
        $siteId = $values['siteId'] ?? null;
        $site = $siteId ? $this->sites->getSiteById((int) $siteId) : new Site;
        abort_if($site === null, 404, "Invalid site ID: {$siteId}");

        $isNew = $site->id === null;
        $isMultiSite = $this->sites->getTotalSites() > 1;
        $group = Field::make(t('Group'), Choice::make('group')
            ->options($this->siteGroups->getAllGroups()->map(fn ($group) => [
                'label' => $group->getName(),
                'value' => $group->id,
            ])->all()))
            ->instructions(t('Which group should this site belong to?'))
            ->required();

        if (! $isNew && $isMultiSite) {
            $group->warning(t('Changing this may result in data loss.'));
        }

        $handle = Handle::make('handle');
        if ($isNew) {
            $handle->source('name');
        }

        $nodes = [
            HiddenField::make('siteId'),
            $group,
            Field::make(t('Name'), Combobox::make('name')
                ->options(SelectOptions::getEnvSuggestions()))
                ->required()
                ->tip(sprintf(
                    '%s [%s](%s)',
                    t('This can begin with an environment variable.'),
                    t('Learn more'),
                    'https://craftcms.com/docs/5.x/configure.html#control-panel-settings',
                )),
            Field::make(t('Handle'), $handle)
                ->required()
                ->instructions(t('How you’ll refer to this site in the templates.')),
            Field::make(t('Language'), Combobox::make('language')
                ->options([
                    ...SelectOptions::getLanguageOptions(true),
                    ...SelectOptions::getLanguageEnvOptions(),
                ])
                ->requireOptionMatch())
                ->required()
                ->instructions(t('The language content in this site will use.'))
                ->tip(t('This can be set to an environment variable with a valid language ID ({examples}).', [
                    'examples' => '`en`/`en-GB`',
                ])),
        ];

        if ($isMultiSite || $isNew) {
            $status = Field::make(t('Status'), Combobox::make('enabled')
                ->options([
                    [
                        'label' => t('Enabled'),
                        'value' => '1',
                        'data' => ['indicator' => ['variant' => 'success']],
                    ],
                    [
                        'label' => t('Disabled'),
                        'value' => '0',
                        'data' => ['indicator' => ['variant' => 'empty']],
                    ],
                    ...$this->booleanEnvOptions(),
                ])
                ->requireOptionMatch())
                ->tip(t('This can be set to an environment variable with a boolean value ({examples})', [
                    'examples' => '`yes`/`no`/`true`/`false`/`on`/`off`/`0`/`1`',
                ]));

            if ($site->primary) {
                $status->warning(t('The primary site cannot be disabled.'));
            }

            $nodes[] = $status;
        }

        if (($isMultiSite || $isNew) && ! $site->primary) {
            $nodes[] = Field::make(t('Make this the primary site'), Lightswitch::make('primary'))
                ->instructions(t('The primary site will be loaded by default on the front end.'));
        } else {
            $nodes[] = HiddenField::make('primary');
        }

        $nodes[] = Field::make(t('This site has its own base URL'), Lightswitch::make('hasUrls'));

        if ($values['hasUrls'] ?? false) {
            $nodes[] = Field::make(t('Base URL'), Combobox::make('baseUrl')
                ->options(SelectOptions::getEnvSuggestions(true, Str::isUrl(...))))
                ->instructions(t('The base URL for the site.'))
                ->tip(sprintf(
                    '%s [%s](%s)',
                    t('This can begin with an environment variable or alias.'),
                    t('Learn more'),
                    'https://craftcms.com/docs/5.x/configure.html#control-panel-settings',
                ));
        }

        return $this->formResolver->resolve(Form::make($nodes), new FormContext(
            values: $values,
            mode: $this->readOnly ? ControlMode::ReadOnly : ControlMode::Editable,
            refreshable: ! $this->readOnly,
        ));
    }

    /** @return array<string, mixed> */
    private function siteValues(Site $site): array
    {
        $enabled = match ($site->getEnabled(false)) {
            true => '1',
            false => '0',
            default => $site->getEnabled(false),
        };

        return [
            'siteId' => $site->id,
            'group' => $site->groupId,
            'name' => $site->getName(false),
            'handle' => $site->handle,
            'language' => $site->getLanguage(false),
            'enabled' => $enabled,
            'primary' => $site->primary,
            'hasUrls' => $site->hasUrls,
            'baseUrl' => $site->getBaseUrl(false) ?? '',
        ];
    }

    /** @return list<array<string, mixed>> */
    private function booleanEnvOptions(): array
    {
        $groups = SelectOptions::getBooleanEnvOptions();
        $groups[0]['options'] = $groups[0]['options']
            ->map(function (array $option): array {
                $enabled = $option['data']['boolean'] === '1';

                return [
                    ...$option,
                    'data' => [
                        ...$option['data'],
                        'hint' => $enabled ? t('Enabled') : t('Disabled'),
                        'indicator' => [
                            'variant' => $enabled ? 'success' : 'empty',
                        ],
                    ],
                ];
            })
            ->all();

        return $groups;
    }
}

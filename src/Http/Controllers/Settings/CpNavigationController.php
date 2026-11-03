<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\CustomNavigations;
use CraftCms\Cms\Cp\Enums\TableAppearance;
use CraftCms\Cms\Cp\NavCustomizer;
use CraftCms\Cms\Cp\Navigation;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Requests\NavCustomizationRequest;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Ui\Controls\Checkbox;
use CraftCms\Cms\Ui\Controls\ConditionBuilder;
use CraftCms\Cms\Ui\Controls\IconPicker;
use CraftCms\Cms\Ui\Controls\Lightswitch;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Nodes\Table;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiPayload;
use CraftCms\Cms\Ui\UiResolver;
use CraftCms\Cms\User\Conditions\UserCondition;
use CraftCms\Cms\User\Data\UserGroup;
use CraftCms\Cms\User\UserGroups;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\AnyOf;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

/**
 * The control panel navigations: the default one, and those for user groups.
 *
 * @since 6.0.0
 */
class CpNavigationController
{
    use RespondsWithFlash;

    private bool $readOnly;

    public function __construct(
        private readonly CustomNavigations $customNavigations,
        private readonly NavCustomizer $navCustomizer,
        private readonly Navigation $navigation,
        private readonly UserGroups $userGroups,
        private readonly UiResolver $uiResolver,
        GeneralConfig $generalConfig,
    ) {
        $this->readOnly = ! $generalConfig->allowAdminChanges;
    }

    /**
     * The default navigation's builder, which is also where user group
     * navigations are listed, in a tab beside it, on editions that have them.
     */
    public function index(): CpScreenResponse
    {
        return $this->form(null, $this->customNavigations->getDefault());
    }

    /** Where the default navigation's builder used to live. */
    public function editDefault(): RedirectResponse
    {
        return to_route('craft.cp.settings.cp-nav.index');
    }

    /**
     * The user group navigations, as a table that lists them in the order
     * they apply.
     */
    private function groupsTable(): UiPayload
    {
        $groupNavigations = $this->customNavigations->getGroupNavigations();
        $taken = $groupNavigations->map(fn (array $navigation) => $navigation['userGroup']->uid)->all();
        $newItems = $this->userGroups->getAllGroups()
            ->reject(fn (UserGroup $group) => in_array($group->uid, $taken, true))
            ->map(fn (UserGroup $group) => [
                'label' => $group->name,
                'url' => route('craft.cp.settings.cp-nav.edit', ['groupUid' => $group->uid]),
            ])
            ->values()
            ->all();

        $rows = $groupNavigations->map(fn (array $navigation): array => [
            'id' => $navigation['userGroup']->uid,
            'name' => [
                'label' => $navigation['userGroup']->name,
                'url' => route('craft.cp.settings.cp-nav.edit', ['groupUid' => $navigation['userGroup']->uid]),
            ],
            'changes' => $this->navCustomizer->summarize($navigation['nav']),
            ...($this->readOnly ? [] : [
                '_deleteUrl' => route('craft.cp.settings.cp-nav.destroy', ['groupUid' => $navigation['userGroup']->uid]),
                '_deleteConfirmMessage' => t('Are you sure you want to delete the navigation for “{name}”?', ['name' => $navigation['userGroup']->name]),
            ]),
        ]);

        return $this->uiResolver->resolve(Ui::make([
            Table::make('cp-navigations')
                ->columns([
                    ['key' => 'name', 'label' => t('User group')],
                    ['key' => 'changes', 'label' => t('Changes')],
                ])
                ->rows($rows)
                ->emptyMessage(t('No user groups have their own navigation yet.'))
                ->appearance(TableAppearance::Aligned)
                ->unless($this->readOnly, fn (Table $table) => $table
                    ->when($newItems !== [], fn (Table $table) => $table->createActionMenu(t('New navigation'), $newItems))
                    ->when(
                        $groupNavigations->count() > 1,
                        fn (Table $table) => $table->reorderable(route('craft.cp.settings.cp-nav.reorder'), t('New order saved.')),
                    )
                    ->when($groupNavigations->isNotEmpty(), fn (Table $table) => $table->deletable())),
        ]), new UiContext);
    }

    public function updateDefault(NavCustomizationRequest $request): Response
    {
        $this->customNavigations->saveDefault(
            $this->navCustomizer->diff($this->navigation->getBaseTree(), $request->nav()),
        );

        return $this->asSuccess(
            t('Navigation saved.'),
            redirect: $this->getPostedRedirectUrl() ?? route('craft.cp.settings.cp-nav.index'),
        );
    }

    /** A user group's navigation, or a new one for a group without one yet. */
    public function edit(string $groupUid): CpScreenResponse
    {
        $group = $this->userGroups->getGroupByUid($groupUid) ?? abort(404);

        return $this->form($group, $this->customNavigations->getGroupNavigation($groupUid)['nav'] ?? []);
    }

    public function update(NavCustomizationRequest $request, string $groupUid): Response
    {
        $group = $this->userGroups->getGroupByUid($groupUid) ?? abort(404);

        $this->customNavigations->saveGroupNavigation(
            $group,
            $this->navCustomizer->diff($this->navigation->getBaseTreeForGroup($group), $request->nav(), $this->customNavigations->getDefault()),
        );

        return $this->asSuccess(
            t('Navigation saved.'),
            redirect: $this->getPostedRedirectUrl() ?? route('craft.cp.settings.cp-nav.edit', ['groupUid' => $groupUid]),
        );
    }

    public function destroy(string $groupUid): Response
    {
        $this->customNavigations->deleteGroupNavigation($groupUid);

        return $this->asSuccess(t('Navigation deleted.'), redirect: route('craft.cp.settings.cp-nav.index'));
    }

    public function reorder(Request $request): Response
    {
        $ids = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['string']])['ids'];

        $this->customNavigations->reorderGroupNavigations(array_values($ids));

        return $this->asSuccess(t('New order saved.'));
    }

    /**
     * The settings for one node of the builder's unsaved tree, as the builder
     * posts it.
     */
    public function itemSettings(Request $request): JsonResponse
    {
        $node = $this->postedNode($request);
        $mode = $this->readOnly ? ControlMode::ReadOnly : ControlMode::Editable;
        $isCustom = $node['source'] === 'custom';
        $isLink = $node['type'] === 'link';

        $ui = Ui::make([
            Field::make(t('Label'), Text::make('label')
                ->mode($mode)
                ->maxLength(255)
                ->placeholder($node['defaultLabel']))
                ->required($isCustom),
        ])
            ->when($isLink, fn (Ui $ui) => $ui->add(
                Field::make(t('URL'), Text::make('url')->mode($mode)->inputMode('url'))
                    ->required()
                    ->instructions(t('A control panel path, or a full URL.')),
                Field::make(null, Checkbox::make('newWindow')->mode($mode)->label(t('Open in a new window'))),
            ))
            ->when($node['kind'] === 'item', fn (Ui $ui) => $ui->add(
                Field::make(t('Icon'), IconPicker::make('icon')->mode($mode)),
            ))
            ->when($isCustom, fn (Ui $ui) => $ui->add(
                Field::make(t('Current User Condition'), ConditionBuilder::make('userCondition')
                    ->mode($mode)
                    ->conditionClass(UserCondition::class)
                    ->forProjectConfig())
                    ->instructions($isLink
                        ? t('Only users who match these rules see this link.')
                        : t('Only users who match these rules see this section.')),
            ))
            ->add(Field::make(t('Show in the navigation'), Lightswitch::make('shown')->mode($mode)));

        return new JsonResponse([
            'ui' => $this->uiResolver->resolve($ui, new UiContext(values: [
                'label' => $node['label'],
                'url' => $node['url'],
                'newWindow' => $node['newWindow'],
                'icon' => $node['icon'],
                'shown' => ! $node['hidden'],
                'userCondition' => $node['userCondition'] ?? [],
            ])),
        ]);
    }

    /**
     * Checks a node's settings before the builder applies them. They're saved
     * with the rest of the tree, which is checked again then.
     */
    public function applyItemSettings(Request $request): Response
    {
        $node = $this->postedNode($request);

        $request->validate([
            'settings.label' => [$node['source'] === 'custom' ? 'required' : 'nullable', 'string', 'max:255'],
            ...($node['type'] === 'link' ? ['settings.url' => ['required', ...NavCustomizationRequest::linkRules()]] : []),
            'settings.userCondition' => NavCustomizationRequest::userConditionRules(),
        ], [
            'settings.url.'.AnyOf::class => t('Enter a control panel path, or a URL starting with http, https, mailto or tel.'),
        ], [
            'settings.label' => t('Label'),
            'settings.url' => t('URL'),
        ]);

        return response()->noContent();
    }

    /**
     * @return array{kind: string, source: string, type: ?string, label: string, defaultLabel: ?string, url: ?string, newWindow: bool, icon: ?string, hidden: bool, userCondition: array<string, mixed>|null}
     */
    private function postedNode(Request $request): array
    {
        $node = $request->validate([
            'node.kind' => ['required', 'in:section,item,child'],
            'node.source' => ['required', 'in:default,custom'],
            'node.type' => ['nullable', 'in:section,link'],
            'node.label' => ['nullable', 'string'],
            'node.defaultLabel' => ['nullable', 'string'],
            'node.url' => ['nullable', 'string'],
            'node.newWindow' => ['boolean'],
            'node.icon' => ['nullable', 'string'],
            'node.hidden' => ['boolean'],
            'node.userCondition' => ['nullable', 'array'],
        ])['node'];

        return [
            'kind' => $node['kind'],
            'source' => $node['source'],
            'type' => $node['type'] ?? null,
            'label' => (string) ($node['label'] ?? ''),
            'defaultLabel' => $node['defaultLabel'] ?? null,
            'url' => $node['url'] ?? null,
            'newWindow' => (bool) ($node['newWindow'] ?? false),
            'icon' => $node['icon'] ?? null,
            'hidden' => (bool) ($node['hidden'] ?? false),
            'userCondition' => $node['userCondition'] ?? null,
        ];
    }

    /**
     * The builder for the default navigation, or a user group's. A group's
     * starts from the default navigation, and is built with the group's
     * permissions, so it only offers what its members can get to.
     *
     * @param  array<mixed>  $nav
     */
    private function form(?UserGroup $group, array $nav): CpScreenResponse
    {
        $title = $group === null ? t('CP Navigation') : (string) $group->name;
        $baseTree = $group === null
            ? $this->navigation->getBaseTree()
            : $this->navigation->getBaseTreeForGroup($group);
        $base = $group === null ? [] : $this->customNavigations->getDefault();

        $response = new CpScreenResponse()
            ->title($title)
            ->addCrumb(t('Settings'), 'settings');

        if ($group !== null) {
            $response->addCrumb(t('CP Navigation'), 'settings/cp-nav#cp-nav-groups');
        }

        return $response
            ->addCrumb($title)
            ->inertiaPage('settings/cp-nav/Edit', [
                'isDefault' => $group === null,
                'tree' => $this->navCustomizer->resolve($baseTree, $nav, $base),
                'defaultTree' => $this->navCustomizer->resolve($baseTree, [], $base),
                'readOnly' => $this->readOnly,
                'saveUrl' => $group === null
                    ? route('craft.cp.settings.cp-nav.update-default')
                    : route('craft.cp.settings.cp-nav.update', ['groupUid' => $group->uid]),
                // Group navigations get a tab beside the default one's builder.
                'groupsTable' => $group === null && Edition::isAtLeast(Edition::Pro)
                    ? $this->groupsTable(...)
                    : null,
            ])
            ->redirectUrl($group === null ? 'settings/cp-nav' : 'settings/cp-nav#cp-nav-groups');
    }
}

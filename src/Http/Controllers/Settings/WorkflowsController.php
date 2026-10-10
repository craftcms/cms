<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Http\Requests\WorkflowRequest;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Http\ViewModels\WorkflowEditViewModel;
use CraftCms\Cms\Ui\Nodes\Table;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Workflow\Models\Workflow;
use CraftCms\Cms\Workflow\Workflows;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class WorkflowsController
{
    use RespondsWithFlash;

    private bool $readOnly;

    public function __construct(
        private readonly Workflows $workflows,
        GeneralConfig $generalConfig,
    ) {
        $this->readOnly = ! $generalConfig->allowAdminChanges;
    }

    public function index(): CpScreenResponse
    {
        $rows = Workflow::query()->orderBy('name')->get()->map(fn (Workflow $workflow): array => [
            'id' => $workflow->id,
            'name' => [
                'label' => $workflow->name,
                'url' => route('craft.cp.settings.workflows.edit', ['workflow' => $workflow->id]),
            ],
            'stages' => $workflow->stages->count(),
            ...($this->readOnly ? [] : [
                '_deleteUrl' => route('craft.cp.settings.workflows.destroy', ['workflow' => $workflow->id]),
                '_deleteConfirmMessage' => t('Are you sure you want to delete “{name}”?', ['name' => $workflow->name]),
            ]),
        ]);

        $table = Table::make('workflows')
            ->columns([
                ['key' => 'name', 'label' => t('Name'), 'sortable' => true],
                ['key' => 'stages', 'label' => t('Stages')],
            ])
            ->rows($rows)
            ->emptyMessage(t('No approval workflows exist yet.'))
            ->unless($this->readOnly, fn (Table $table) => $table
                ->createAction(t('New workflow'), route('craft.cp.settings.workflows.create'))
                ->createActionInPageHeader()
                ->deletable());

        return new CpScreenResponse()
            ->title(t('Workflows'))
            ->crumbs([
                ['label' => t('Settings'), 'href' => route('craft.cp.settings.index')],
                ['label' => t('Workflows')],
            ])
            ->ui(Ui::make([$table]));
    }

    public function create(): CpScreenResponse
    {
        return $this->form(new Workflow);
    }

    public function edit(Workflow $workflow): CpScreenResponse
    {
        return $this->form($workflow);
    }

    public function store(WorkflowRequest $request): Response
    {
        return $this->save($request, new Workflow);
    }

    public function update(WorkflowRequest $request, Workflow $workflow): Response
    {
        return $this->save($request, $workflow);
    }

    public function destroy(Workflow $workflow): Response
    {
        if ($workflow->sections()->exists()) {
            return $this->asFailure(t('This workflow cannot be deleted while it is assigned to a section.'));
        }

        $this->workflows->deleteWorkflow($workflow);

        return $this->asSuccess(t('Workflow deleted.'), redirect: action([self::class, 'index']));
    }

    private function save(WorkflowRequest $request, Workflow $workflow): Response
    {
        $data = $request->workflowData();
        $workflow->fill($data);

        $this->workflows->saveWorkflow($workflow);

        return $this->asSuccess(
            t('Workflow saved.'),
            redirect: $this->getPostedRedirectUrl($workflow)
                ?? action([self::class, 'edit'], $workflow->id),
        );
    }

    private function form(Workflow $workflow): CpScreenResponse
    {
        $title = $workflow->exists ? $workflow->name : t('Create a new workflow');

        return new CpScreenResponse()
            ->title($title)
            ->addCrumb(t('Settings'), 'settings')
            ->addCrumb(t('Workflows'), 'settings/workflows')
            ->addCrumb($title)
            ->inertiaPage('settings/workflows/Edit', app(WorkflowEditViewModel::class, [
                'workflow' => $workflow,
                'readOnly' => $this->readOnly,
            ]))
            ->redirectUrl('settings/workflows');
    }
}

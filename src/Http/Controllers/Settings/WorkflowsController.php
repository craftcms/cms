<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Http\Requests\WorkflowRequest;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Http\ViewModels\WorkflowEditViewModel;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Workflow\Models\Workflow;
use CraftCms\Cms\Workflow\Workflows;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

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
        return new CpScreenResponse()
            ->title(t('Workflows'))
            ->crumbs([
                ['label' => t('Settings'), 'href' => Url::cpUrl('settings')],
                ['label' => t('Workflows')],
            ])
            ->inertiaPage('settings/workflows/Index', [
                'workflows' => Workflow::query()
                    ->orderBy('name')
                    ->get()
                    ->map(fn (Workflow $workflow): array => [
                        'id' => $workflow->id,
                        'name' => $workflow->name,
                        'stages' => $workflow->stages->count(),
                    ]),
                'readOnly' => $this->readOnly,
            ]);
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
            redirect: action([self::class, 'edit'], $workflow->id),
        );
    }

    private function form(Workflow $workflow): CpScreenResponse
    {
        $title = $workflow->exists ? $workflow->name : t('New workflow');

        return new CpScreenResponse()
            ->title($title)
            ->addCrumb(t('Settings'), 'settings')
            ->addCrumb(t('Workflows'), 'settings/workflows')
            ->inertiaPage('settings/workflows/Edit', app(WorkflowEditViewModel::class, [
                'workflow' => $workflow,
                'readOnly' => $this->readOnly,
            ]));
    }
}

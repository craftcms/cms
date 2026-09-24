<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Http\Requests\TableRequest;
use CraftCms\Cms\Http\Requests\WorkflowRequest;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Http\ViewModels\WorkflowEditViewModel;
use CraftCms\Cms\Support\Arr;
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

    public function index(TableRequest $request): CpScreenResponse
    {
        $searchTerm = trim($request->search() ?? '');
        $pageParam = Cms::config()->getPageTriggerParam();
        $paginator = Workflow::query()
            ->when($searchTerm !== '', fn ($query) => $query->where('name', 'like', "%{$searchTerm}%"))
            ->orderBy('name', $request->sortDir() === SORT_DESC ? 'desc' : 'asc')
            ->paginate($request->limit(), ['*'], $pageParam, $request->page())
            ->appends($request->except($pageParam));
        $pagination = Arr::only($paginator->toArray(), [
            'total',
            'per_page',
            'current_page',
            'last_page',
            'next_page_url',
            'prev_page_url',
            'from',
            'to',
        ]);

        return new CpScreenResponse()
            ->title(t('Workflows'))
            ->crumbs([
                ['label' => t('Settings'), 'href' => Url::cpUrl('settings')],
                ['label' => t('Workflows')],
            ])
            ->inertiaPage('settings/workflows/Index', [
                'searchTerm' => $request->search(),
                'sort' => $request->sort(),
                'data' => fn () => $paginator->getCollection()
                    ->map(fn (Workflow $workflow): array => [
                        'id' => $workflow->id,
                        'name' => $workflow->name,
                        'stages' => $workflow->stages->count(),
                    ]),
                'pagination' => fn () => $pagination,
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
            ->inertiaPage('settings/workflows/Edit', app(WorkflowEditViewModel::class, [
                'workflow' => $workflow,
                'readOnly' => $this->readOnly,
            ]))
            ->redirectUrl('settings/workflows');
    }
}

<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Http\Controllers\Settings\WorkflowsController;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Ui\Controls\Table;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Nodes\Heading;
use CraftCms\Cms\Ui\Nodes\Missing;
use CraftCms\Cms\Ui\Nodes\Separator;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiPayload;
use CraftCms\Cms\Ui\UiResolver;
use CraftCms\Cms\Workflow\Contracts\WorkflowStageInterface;
use CraftCms\Cms\Workflow\Data\WorkflowStageData;
use CraftCms\Cms\Workflow\Models\Workflow;
use CraftCms\Cms\Workflow\Stages\MissingWorkflowStage;
use CraftCms\Cms\Workflow\WorkflowStageTypes;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class WorkflowEditViewModel extends ViewModel
{
    public function __construct(
        private readonly Workflow $workflow,
        private readonly bool $readOnly,
        private readonly UiResolver $uiResolver,
        private readonly WorkflowStageTypes $workflowStageTypes,
    ) {}

    public function ui(): UiPayload
    {
        return $this->uiResolver->resolve(Ui::make([
            Field::make(t('Name'), Text::make('name')->autofocus())
                ->instructions(t('What this workflow will be called in the control panel.'))
                ->required(),
            Separator::make('review-stages-separator'),
            Heading::make('review-stages-heading', t('Stages'))
                ->description(t('Stages run in order and decide when the workflow may advance.')),
            Field::make(control: Table::make('stages')->allowAdd()->allowDelete()->allowReorder()->minRows(1)),
        ]), new UiContext(
            values: $this->initialValues(),
            mode: $this->readOnly ? ControlMode::ReadOnly : ControlMode::Editable,
        ));
    }

    /** @return list<array{type: string, label: string, settings: array<string, mixed>, settingsUi: UiPayload|null}> */
    public function stageTypes(): array
    {
        return $this->workflowStageTypes->types()
            ->filter(fn (string $type): bool => $type::isSelectable())
            ->map(function (string $type): array {
                /** @var WorkflowStageInterface $stage */
                $stage = ComponentHelper::createComponent($type, WorkflowStageInterface::class);

                return [
                    'type' => $type,
                    'label' => $type::displayName(),
                    'settings' => $stage->getSettings(),
                    'settingsUi' => $this->settingsUi($stage),
                ];
            })
            ->values()
            ->all();
    }

    /** @return array{method: 'patch'|'post', url: string} */
    public function submit(): array
    {
        return [
            'method' => $this->workflow->exists ? 'patch' : 'post',
            'url' => $this->workflow->exists
                ? action([WorkflowsController::class, 'update'], $this->workflow)
                : action([WorkflowsController::class, 'store']),
        ];
    }

    /** @return array{confirm: string, label: string, url: string}|null */
    public function deleteAction(): ?array
    {
        if ($this->readOnly || ! $this->workflow->exists) {
            return null;
        }

        return [
            'confirm' => t('Are you sure you want to delete this workflow?'),
            'label' => t('Delete workflow'),
            'url' => action([WorkflowsController::class, 'destroy'], $this->workflow),
        ];
    }

    /** @return array<string, mixed> */
    private function initialValues(): array
    {
        $defaultStageType = $this->workflowStageTypes->types()
            ->first(fn (string $type): bool => $type::isSelectable());

        return [
            'name' => $this->workflow->name ?? '',
            'stages' => $this->workflow->stages->isEmpty() && $defaultStageType !== null
                ? [$this->stageData(new WorkflowStageData(
                    uid: Str::uuid7()->toString(),
                    name: t('Review'),
                    type: $defaultStageType,
                    settings: ComponentHelper::createComponent($defaultStageType, WorkflowStageInterface::class)->getSettings(),
                ))]
                : $this->workflow->stages->map($this->stageData(...))->all(),
        ];
    }

    private function stageData(WorkflowStageData $stage): WorkflowStageData
    {
        $component = $stage->component();

        return new WorkflowStageData(
            uid: $stage->uid,
            name: $stage->name,
            type: $stage->type,
            settings: $stage->settings,
            settingsUi: $this->settingsUi($component),
        );
    }

    private function settingsUi(WorkflowStageInterface $stage): ?UiPayload
    {
        if ($stage instanceof MissingWorkflowStage) {
            return $this->uiResolver->resolve(
                Ui::make([Missing::make('missing-workflow-stage', $stage->expectedType)]),
                new UiContext(values: $stage->getSettings()),
            );
        }

        $ui = $stage->settingsUi(new UiContext(values: $stage->getSettings()));

        return $ui === null
            ? null
            : $this->uiResolver->resolve($ui, new UiContext(values: $stage->getSettings()));
    }
}

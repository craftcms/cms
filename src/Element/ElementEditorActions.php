<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Workflow\Contracts\WorkflowableInterface;
use CraftCms\Cms\Workflow\Data\WorkflowReviewData;
use CraftCms\Cms\Workflow\Workflows;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;

use function CraftCms\Cms\t;

class ElementEditorActions
{
    public function __construct(
        private readonly Workflows $workflows,
    ) {}

    /**
     * @return array{
     *     primary: array{label: string, actionUrl: string|null, params: array<string, mixed>, redirect: null, tabId: string|null},
     *     menu: list<array{label: string, actionUrl: string|null, params: array<string, mixed>, redirect: string|null, shortcut: bool, shift: bool}>,
     *     buttons: list<array<string, mixed>>,
     * }
     */
    public function for(
        ElementInterface $element,
        bool $canSave,
        ?WorkflowReviewData $review = null,
        ?string $primaryLabel = null,
        bool $isNew = false,
    ): array {
        $actions = [
            'primary' => $this->primaryAction($element, $primaryLabel),
            'menu' => $this->menuActions($element, $canSave),
            'buttons' => $this->buttonActions($element, $canSave),
        ];

        if (
            ! $element instanceof WorkflowableInterface ||
            ! $this->workflows->requiresApproval($element)
        ) {
            return $actions;
        }

        $workflowSaveAction = [
            'actionUrl' => Url::actionUrl('elements/save-draft'),
            'params' => [
                ...$this->identityParams($element),
                'dropProvisional' => 1,
                'workflowSave' => 1,
            ],
        ];

        $actions['primary'] = [
            ...$actions['primary'],
            ...$workflowSaveAction,
        ];

        if ($element->getIsUnpublishedDraft()) {
            $actions['menu'] = [[
                'label' => t('Save draft'),
                ...$workflowSaveAction,
                'redirect' => null,
                'shortcut' => true,
                'shift' => false,
            ]];

            if ($canSave && Gate::check('duplicateAsDraft', $element)) {
                $actions['menu'][] = [
                    ...$this->duplicateAsDraftAction($element),
                    'shortcut' => false,
                    'shift' => false,
                ];
            }

            if (! $isNew) {
                $actions['primary']['label'] = t('Save draft');

                if ($review?->canSubmit) {
                    $actions['primary'] = [
                        ...$actions['primary'],
                        'label' => t('Request review'),
                        'tabId' => 'workflow',
                    ];
                }
            }
        } else {
            $actions['menu'] = array_map(
                fn (array $action): array => $action['actionUrl'] === null
                    ? [
                        ...$action,
                        ...$workflowSaveAction,
                        'params' => [...$workflowSaveAction['params'], ...$action['params']],
                    ]
                    : $action,
                $actions['menu'],
            );
        }

        if ($review !== null) {
            $actions['buttons'] = array_map(
                fn (array $action): array => $action['actionUrl'] === Url::actionUrl('elements/apply-draft')
                    ? [
                        ...$action,
                        'params' => [
                            ...$action['params'],
                            'workflowRunId' => $review->runId,
                            'workflowCurrentStage' => $review->currentStage,
                        ],
                        'includeFormData' => ! $review->canApply,
                        'disabled' => ! $review->canApply,
                        'disabledReason' => $review->applyDisabledReason,
                    ]
                    : $action,
                $actions['buttons'],
            );
        }

        return $actions;
    }

    /** @return array{label: string, actionUrl: null, params: array{}, redirect: null, tabId: null} */
    private function primaryAction(ElementInterface $element, ?string $label): array
    {
        $label ??= match (true) {
            $element->getIsUnpublishedDraft() => Gate::check('saveCanonical', $element)
                ? mb_ucfirst(t('Create {type}', ['type' => $element::lowerDisplayName()]))
                : mb_ucfirst(t('Save {type}', ['type' => t('draft')])),
            $element->getIsDraft() && ! $element->isProvisionalDraft => mb_ucfirst(t('Save {type}', ['type' => t('draft')])),
            default => t('Save'),
        };

        return [
            'label' => $label,
            'actionUrl' => null,
            'params' => [],
            'redirect' => null,
            'tabId' => null,
        ];
    }

    /** @return list<array{label: string, actionUrl: string|null, params: array<string, mixed>, redirect: string|null, shortcut: bool, shift: bool}> */
    private function menuActions(ElementInterface $element, bool $canSave): array
    {
        if (! $canSave) {
            return [];
        }

        return array_map(fn (array $action): array => [
            'label' => (string) $action['label'],
            'actionUrl' => isset($action['action']) ? Url::actionUrl($action['action']) : null,
            'params' => isset($action['action'])
                ? [...$this->identityParams($element), ...($action['params'] ?? [])]
                : ($action['params'] ?? []),
            'redirect' => isset($action['redirect']) ? Crypt::encrypt($action['redirect']) : null,
            'shortcut' => (bool) ($action['shortcut'] ?? false),
            'shift' => (bool) ($action['shift'] ?? false),
        ], $element->getAltActions());
    }

    /** @return list<array<string, mixed>> */
    private function buttonActions(ElementInterface $element, bool $canSave): array
    {
        $canonical = $element->getCanonical(true);
        $isCurrent = $element->getIsCanonical() || $element->isProvisionalDraft;
        $actions = [];

        if ($isCurrent && ! $element->getIsUnpublishedDraft() && Gate::check('createDrafts', $canonical)) {
            $actions[] = [
                'label' => t('Create a draft'),
                'actionUrl' => Url::actionUrl('elements/save-draft'),
                'params' => [
                    ...$this->identityParams($element),
                    'dropProvisional' => 1,
                ],
                'redirect' => Crypt::encrypt('{cpEditUrl}'),
                'variant' => 'outline',
            ];
        }

        if (
            $element->getIsDraft() &&
            ! $element->isProvisionalDraft &&
            $canSave &&
            Gate::check('saveCanonical', $element)
        ) {
            $actions[] = [
                'label' => t('Apply draft'),
                'actionUrl' => Url::actionUrl('elements/apply-draft'),
                'params' => [
                    ...$this->identityParams($element),
                    'draftId' => $element->draftId,
                ],
                'redirect' => Crypt::encrypt('{cpEditUrl}'),
                'variant' => 'secondary',
            ];
        }

        if (
            $element->getIsRevision() &&
            $element->hasRevisions() &&
            Gate::check('saveCanonical', $element)
        ) {
            $actions[] = [
                'label' => t('Revert content from this revision'),
                'actionUrl' => Url::actionUrl('elements/revert'),
                'params' => [
                    ...$this->identityParams($element),
                    'revisionId' => $element->revisionId,
                ],
                'redirect' => Crypt::encrypt('{cpEditUrl}'),
                'variant' => 'outline',
            ];
        }

        if (
            ! $canSave &&
            ! $element->getIsRevision() &&
            Gate::check('duplicateAsDraft', $element)
        ) {
            $actions[] = [
                ...$this->duplicateAsDraftAction($element),
                'variant' => 'outline',
            ];
        }

        return $actions;
    }

    /** @return array{label: string, actionUrl: string, params: array<string, mixed>, redirect: string} */
    private function duplicateAsDraftAction(ElementInterface $element): array
    {
        return [
            'label' => t('Save as a new {type}', ['type' => $element::lowerDisplayName()]),
            'actionUrl' => Url::actionUrl('elements/duplicate'),
            'params' => [
                ...$this->identityParams($element),
                'asUnpublishedDraft' => 1,
            ],
            'redirect' => Crypt::encrypt('{cpEditUrl}'),
        ];
    }

    /** @return array{elementType: class-string<ElementInterface>, elementId: int|null, siteId: int|null} */
    private function identityParams(ElementInterface $element): array
    {
        return [
            'elementType' => $element::class,
            'elementId' => $element->getCanonical(true)->id,
            'siteId' => $element->siteId,
        ];
    }
}

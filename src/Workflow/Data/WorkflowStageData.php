<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow\Data;

use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Component\Exceptions\MissingComponentException;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Workflow\Contracts\WorkflowStageInterface;
use CraftCms\Cms\Workflow\Stages\MissingWorkflowStage;

readonly class WorkflowStageData
{
    /** @param array<string, mixed> $settings */
    public function __construct(
        public string $uid,
        public string $name,
        public string $type,
        public array $settings,
        public ?FormPayload $settingsForm = null,
    ) {}

    /** @param array{uid: string, name: string, type: string, settings?: array<string, mixed>} $data */
    public static function fromArray(array $data): self
    {
        return new self(
            uid: $data['uid'],
            name: $data['name'],
            type: $data['type'],
            settings: $data['settings'] ?? [],
        );
    }

    public function component(): WorkflowStageInterface
    {
        $config = ['type' => $this->type, 'settings' => $this->settings];

        try {
            return ComponentHelper::createComponent($config, WorkflowStageInterface::class);
        } catch (MissingComponentException $exception) {
            return new MissingWorkflowStage([
                'expectedType' => $this->type,
                'errorMessage' => $exception->getMessage(),
                'settings' => $this->settings,
            ]);
        }
    }

    /** @return array{uid: string, name: string, type: string, settings: array<string, mixed>} */
    public function getConfig(): array
    {
        return [
            'uid' => $this->uid,
            'name' => trim($this->name),
            'type' => $this->type,
            'settings' => $this->settings,
        ];
    }
}

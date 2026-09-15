<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Form\Controls\Handle;
use CraftCms\Cms\Form\Controls\Table;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Controls\Textarea;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Field as FormField;
use CraftCms\Cms\Form\Nodes\HiddenField;
use CraftCms\Cms\Http\Controllers\Import\ImportRunController;
use CraftCms\Cms\Import\Data\ImportRun;
use CraftCms\Cms\Import\ImportConfig;

use function CraftCms\Cms\t;

class ImportRunEditViewModel extends ViewModel
{
    public function __construct(
        private readonly ImportRun $run,
        private readonly ImportConfig $importConfigService,
        private readonly FormResolver $formResolver,
        private readonly bool $readOnly = false,
        private readonly bool $canSave = true,
    ) {}

    public function form(): FormPayload
    {
        $mode = $this->readOnly || ! $this->canSave ? ControlMode::ReadOnly : ControlMode::Editable;

        $handle = Handle::make('handle');
        if (! $this->run->uid) {
            $handle->source('name');
        }

        return $this->formResolver->resolve(Form::make([
            HiddenField::make('uid'),
            FormField::make(t('Name'), Text::make('name')->autofocus())
                ->required(),
            FormField::make(t('Handle'), $handle)
                ->required(),
            FormField::make(t('Description'), Textarea::make('description')),
            FormField::make(t('Steps'), Table::make('steps')
                ->columns([
                    'config' => [
                        'type' => 'select',
                        'heading' => t('Config'),
                        'options' => $this->configOptions(),
                    ],
                    'batchSize' => [
                        'type' => 'number',
                        'heading' => t('Custom batch size'),
                        'info' => t('By default, each step will be run in batches containing up to 100 items. You can provide a different size, if you wish. Set to 0 to disable batching.'),
                    ],
                ])
                ->allowAdd()
                ->allowDelete()
                ->allowReorder())
                ->instructions(t('Define steps for your import.'))
                ->required(),
        ]), new FormContext(
            values: [
                'uid' => $this->run->uid,
                'name' => $this->run->name,
                'handle' => $this->run->handle,
                'description' => $this->run->description,
                'steps' => $this->run->steps ?? [],
            ],
            mode: $mode,
        ));
    }

    /** @return array{method: 'post', url: string} */
    public function submit(): array
    {
        return [
            'method' => 'post',
            'url' => action([ImportRunController::class, 'store']),
        ];
    }

    /** @return list<array{label: string, value: string|null}> */
    private function configOptions(): array
    {
        return $this->importConfigService->getAllConfigs()
            ->map(fn ($config) => [
                'label' => $config->name,
                'value' => $config->isEditable() ? $config->uid : $config->handle,
            ])
            ->prepend(['label' => t('Please select'), 'value' => null])
            ->values()
            ->all();
    }
}

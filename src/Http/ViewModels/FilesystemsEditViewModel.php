<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Filesystem\Filesystems;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\Handle;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Form\Nodes\HiddenField;
use CraftCms\Cms\Http\Controllers\Settings\FilesystemsController;
use CraftCms\Cms\Support\Arr;

use function CraftCms\Cms\t;

class FilesystemsEditViewModel extends ViewModel
{
    private readonly FsInterface $filesystem;

    public function __construct(
        ?FsInterface $filesystem,
        private readonly Filesystems $filesystems,
        private readonly FormResolver $formResolver,
        private readonly ?string $oldHandle = null,
        private readonly bool $readOnly = false,
    ) {
        $this->filesystem = $filesystem ?? app()->make($filesystems->getAllFilesystemTypes()->firstOrFail());
    }

    public function form(): FormPayload
    {
        $values = [
            'name' => $this->filesystem->name,
            'handle' => $this->filesystem->handle,
            'oldHandle' => $this->oldHandle,
            'type' => $this->filesystem::class,
            'settings' => $this->filesystem->getSettings(),
        ];
        $errors = $this->filesystem->errors()->getMessages();
        $mode = $this->readOnly ? ControlMode::ReadOnly : ControlMode::Editable;
        $handle = Handle::make('handle');

        if ($this->oldHandle === null) {
            $handle->source('name');
        }

        $form = $this->formResolver->resolve(Form::make([
            HiddenField::make('oldHandle'),
            Field::make(t('Name'), Text::make('name')->autocomplete(false)->autofocus())->required(),
            Field::make(t('Handle'), $handle)->required(),
            Field::make(t('Filesystem Type'), Choice::make('type')->options($this->filesystemOptions())->reactive())
                ->instructions(t('What type of filesystem is this?')),
        ]), new FormContext(
            values: $values,
            errors: Arr::only($errors, ['name', 'handle', 'type']),
            mode: $mode,
            refreshable: true,
        ));
        $settingsContext = new FormContext(
            namespace: 'settings',
            values: $values,
            errors: Arr::except($errors, ['name', 'handle', 'type']),
            mode: $mode,
            refreshable: true,
        );
        $settings = $this->formResolver->resolve(
            $this->filesystem->settingsForm($settingsContext) ?? Form::make(),
            $settingsContext,
        );

        return new FormPayload(
            scope: [],
            refreshable: true,
            nodes: [...$form->nodes, ...$settings->nodes],
            values: $values,
            errors: [...$form->errors, ...$settings->errors],
            globalErrors: [...$form->globalErrors, ...$settings->globalErrors],
        );
    }

    /** @return array{method: 'post', url: string} */
    public function submit(): array
    {
        return [
            'method' => 'post',
            'url' => action([FilesystemsController::class, 'store']),
        ];
    }

    public function refreshUrl(): ?string
    {
        return $this->readOnly
            ? null
            : action([FilesystemsController::class, 'renderForm']);
    }

    /** @return list<array{value: class-string<FsInterface>, label: string}> */
    private function filesystemOptions(): array
    {
        return $this->filesystems->getAllFilesystemTypes()
            ->map(fn (string $type): array => [
                'value' => $type,
                'label' => $type::displayName(),
            ])
            ->sortBy('label')
            ->values()
            ->all();
    }
}

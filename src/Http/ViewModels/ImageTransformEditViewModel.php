<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Asset\AssetTransforms;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\Color;
use CraftCms\Cms\Form\Controls\Combobox;
use CraftCms\Cms\Form\Controls\Handle;
use CraftCms\Cms\Form\Controls\Lightswitch;
use CraftCms\Cms\Form\Controls\Number;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Enums\ChoicePresentation;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Form\Nodes\HiddenField;
use CraftCms\Cms\Http\Controllers\Settings\ImageTransformsController;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\Enums\ImageTransformFormat;
use CraftCms\Cms\Image\Enums\ImageTransformInterlace;
use CraftCms\Cms\Image\Enums\ImageTransformMode;
use CraftCms\Cms\Image\Enums\ImageTransformPosition;
use CraftCms\Cms\Image\Enums\ImageTransformQuality;
use CraftCms\Cms\Image\Images;

use function CraftCms\Cms\t;

class ImageTransformEditViewModel extends ViewModel
{
    /** @param array<string, mixed>|null $values */
    public function __construct(
        private readonly ImageTransform $transform,
        private readonly Images $images,
        private readonly FormResolver $formResolver,
        private readonly AssetTransforms $assetTransforms,
        private readonly bool $readOnly = false,
        private readonly ?array $values = null,
    ) {}

    public function form(): FormPayload
    {
        $values = $this->values ?? $this->initialValues();
        $mode = (string) $values['mode'];
        $handle = Handle::make('handle');

        if ($this->transform->id === null) {
            $handle->source('name');
        }

        $form = Form::make([
            HiddenField::make('transformId'),
            Field::make(t('Name'), Text::make('name')->autofocus())->required(),
            Field::make(t('Handle'), $handle)->required(),
            Field::make(t('Asset Transform Driver'), Choice::make('driver')->options($this->driverOptions())),
            Field::make(
                t('Mode'),
                Choice::make('mode')
                    ->options(ImageTransformMode::asOptions())
                    ->presentation(ChoicePresentation::Radios),
            )->required(),
        ]);

        $form->add(
            $mode === ImageTransformMode::Letterbox->value
                ? Field::make(t('Fill Color'), Color::make('fill'))
                : HiddenField::make('fill'),
        );

        $form->add(
            in_array($mode, [ImageTransformMode::Crop->value, ImageTransformMode::Letterbox->value], true)
                ? Field::make(
                    $mode === ImageTransformMode::Letterbox->value
                        ? t('Image Position')
                        : t('Default Focal Point'),
                    Choice::make('position')
                        ->options(ImageTransformPosition::asOptions())
                        ->presentation(ChoicePresentation::Radios),
                )
                : HiddenField::make('position'),
            Field::make(t('Width'), Number::make('width')->min(1)->size(5)),
            Field::make(t('Height'), Number::make('height')->min(1)->size(5)),
            Field::make(t('Allow Upscaling'), Lightswitch::make('upscale')),
            Field::make(
                t('Quality'),
                Combobox::make('quality')
                    ->options($this->qualityOptions())
                    ->placeholder(t('Auto'))
                    ->showAllOnEmpty(),
            ),
            Field::make(
                t('Interlacing'),
                Choice::make('interlace')->options(ImageTransformInterlace::asOptions()),
            ),
            Field::make(
                t('Image Format'),
                Choice::make('format')->options($this->formatOptions($values['format'] ?? null)),
            )->instructions(t('The image format that transformed images should use.')),
        );

        $form->add(...array_values($this->assetTransforms->getOperationFields()));

        return $this->formResolver->resolve($form, new FormContext(
            values: $values,
            errors: $this->transform->errors()->getMessages(),
            mode: $this->readOnly ? ControlMode::ReadOnly : ControlMode::Editable,
            refreshable: ! $this->readOnly,
        ));
    }

    /** @return array{method: 'post', url: string} */
    public function submit(): array
    {
        return [
            'method' => 'post',
            'url' => action([ImageTransformsController::class, 'store']),
        ];
    }

    public function refreshUrl(): ?string
    {
        return $this->readOnly
            ? null
            : action([ImageTransformsController::class, 'renderForm']);
    }

    /** @return array<string, mixed> */
    private function initialValues(): array
    {
        return [
            'transformId' => $this->transform->id,
            'name' => $this->transform->name ?? '',
            'handle' => $this->transform->handle ?? '',
            'driver' => $this->transform->driver ?? '',
            'width' => $this->transform->width ?? '',
            'height' => $this->transform->height ?? '',
            'mode' => $this->transform->mode,
            'position' => $this->transform->position,
            'quality' => $this->transform->quality ?? '',
            'interlace' => $this->transform->interlace,
            'format' => $this->transform->format ?? '',
            'fill' => $this->transform->fill && $this->transform->fill !== 'transparent'
                ? ltrim($this->transform->fill, '#')
                : '',
            'upscale' => $this->transform->upscale,
            ...$this->transform->getCustomOperations(),
        ];
    }

    /** @return list<array{label: string, value: string}> */
    private function driverOptions(): array
    {
        $options = collect($this->assetTransforms->getDriverDefinitions())
            ->map(fn ($definition, string $handle): array => [
                'label' => $definition->name,
                'value' => $handle,
            ]);

        if ($this->transform->driver !== null && $options->doesntContain('value', $this->transform->driver)) {
            $options->put($this->transform->driver, [
                'label' => t('{handle} (Unavailable)', ['handle' => $this->transform->driver]),
                'value' => $this->transform->driver,
            ]);
        }

        return $options
            ->prepend(['label' => t('Default'), 'value' => ''])
            ->values()
            ->all();
    }

    /** @return list<array{label: string, value: string}> */
    private function qualityOptions(): array
    {
        return collect(ImageTransformQuality::asOptions())
            ->map(fn (array $option): array => [
                ...$option,
                'value' => (string) $option['value'],
            ])
            ->prepend(['label' => t('Auto'), 'value' => ''])
            ->all();
    }

    /** @return list<array{label: string, value: string}> */
    private function formatOptions(mixed $format): array
    {
        return collect(ImageTransformFormat::asOptions())
            ->prepend(['label' => t('Auto'), 'value' => ''])
            ->reject(fn (array $option): bool => $format !== $option['value']
                && $option['value'] !== ''
                && ! $this->images->supportsFormat($option['value']))
            ->values()
            ->all();
    }
}

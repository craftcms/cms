<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Volumes;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\Combobox;
use CraftCms\Cms\Form\Controls\FieldLayoutDesigner;
use CraftCms\Cms\Form\Controls\FilesystemSelect;
use CraftCms\Cms\Form\Controls\Handle;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Form\Nodes\HiddenField;
use CraftCms\Cms\Form\Nodes\Separator;
use CraftCms\Cms\Http\Controllers\Settings\VolumesController;
use CraftCms\Cms\Support\Facades\Sites;

use function CraftCms\Cms\t;

class VolumeEditViewModel extends ViewModel
{
    /** @param array<string, mixed>|null $values */
    public function __construct(
        private readonly Volume $volume,
        private readonly Volumes $volumes,
        private readonly FormResolver $formResolver,
        private readonly bool $readOnly = false,
        private readonly ?array $values = null,
    ) {}

    public function form(): FormPayload
    {
        $values = $this->values ?? $this->initialValues();
        $handle = Handle::make('handle');
        $objectTemplateTip = SelectOptions::getObjectTemplateTip();
        $objectTemplateTriggers = SelectOptions::getObjectTemplateTextExpanderTriggers(
            Asset::class,
            [$this->volume->getFieldLayout()],
        );
        $disabledFilesystemTargets = $this->volumes->getAllVolumes()
            ->reject(fn (Volume $volume): bool => $volume->id === $this->volume->id || (bool) $volume->getSubpath())
            ->map(fn (Volume $volume): ?string => $volume->getResolvedFsTarget())
            ->filter(fn (?string $target): bool => $target !== null)
            ->values()
            ->all();

        if ($this->volume->id === null && empty($values['handle'])) {
            $handle->source('name');
        }

        $form = Form::make([
            HiddenField::make('volumeId'),
            Field::make(t('Name'), Text::make('name')->autofocus())->required(),
            Field::make(t('Handle'), $handle)->required(),
            Separator::make('filesystem-separator'),
            Field::make(
                t('Asset Filesystem'),
                FilesystemSelect::make('fsHandle')
                    ->disabledTargets($disabledFilesystemTargets)
                    ->emptyOption(t('Select a filesystem'))
                    ->includeEnvVars()
                    ->create(),
            )
                ->instructions(t('Choose which filesystem assets should be stored in.'))
                ->tip(t('This can be set to an environment variable matching one of the option values.'))
                ->required(),
            // CONFLICT-REVIEW: 6.x swapped the Twig subpath inputs from `autosuggestField`
            // (suggestEnvVars) to `textField` + env text expander triggers. This branch had
            // already ported them to a Combobox seeded with the same env suggestions, so the
            // Combobox is kept here rather than redesigning the control during the merge.
            // Switch these two fields to Text::make(...)->textExpanderTriggers(
            // SelectOptions::getEnvTextExpanderTriggers()) if the text expander is meant to
            // replace the combobox everywhere.
            Field::make(
                t('Subpath'),
                Combobox::make('subpath')
                    ->options(SelectOptions::getEnvSuggestions())
                    ->showAllOnEmpty(),
            )
                ->instructions(t('Where assets should be stored on the filesystem.'))
                ->tip(t('This can begin with an environment variable.')),
            Field::make(
                t('Transform Filesystem'),
                FilesystemSelect::make('transformFsHandle')
                    ->disabledTargets($disabledFilesystemTargets)
                    ->emptyOption(t('Same as asset filesystem'))
                    ->includeEnvVars()
                    ->create()
                    ->placeholder(t('Same as asset filesystem'))
                    ->clearable(),
            )
                ->instructions(t('Choose which filesystem image transforms should be stored in.'))
                ->tip(t('This can be set to an environment variable matching one of the option values.')),
            Field::make(
                t('Transform Subpath'),
                Combobox::make('transformSubpath')
                    ->options(SelectOptions::getEnvSuggestions())
                    ->showAllOnEmpty(),
            )
                ->instructions(t('Where transforms should be stored on the filesystem.'))
                ->tip(t('This can begin with an environment variable.')),
        ]);

        if (Sites::isMultiSite()) {
            $form->add(
                Separator::make('translation-separator'),
                Field::make(
                    t('{name} Translation Method', ['name' => t('Title')]),
                    Choice::make('titleTranslationMethod')->options(TranslationMethod::asOptions()),
                )->instructions(t('How should {name} values be translated?', ['name' => t('Title')])),
            );

            if (($values['titleTranslationMethod'] ?? null) === TranslationMethod::Custom->value) {
                $form->add(Field::make(
                    t('{name} Translation Key Format', ['name' => t('Title')]),
                    Text::make('titleTranslationKeyFormat')
                        ->monospace()
                        ->textExpanderTriggers($objectTemplateTriggers),
                )
                    ->instructions(t('Template that defines the {name} field’s custom “translation key” format. Values will be copied to all sites that produce the same key.', [
                        'name' => t('Title'),
                    ]))
                    ->tip($objectTemplateTip));
            }

            $form->add(Field::make(
                t('{name} Translation Method', ['name' => t('Alternative Text')]),
                Choice::make('altTranslationMethod')->options(TranslationMethod::asOptions()),
            )->instructions(t('How should {name} values be translated?', ['name' => t('Alternative Text')])));

            if (($values['altTranslationMethod'] ?? null) === TranslationMethod::Custom->value) {
                $form->add(Field::make(
                    t('{name} Translation Key Format', ['name' => t('Alternative Text')]),
                    Text::make('altTranslationKeyFormat')
                        ->monospace()
                        ->textExpanderTriggers($objectTemplateTriggers),
                )
                    ->instructions(t('Template that defines the {name} field’s custom “translation key” format. Values will be copied to all sites that produce the same key.', [
                        'name' => t('Alternative Text'),
                    ]))
                    ->tip($objectTemplateTip));
            }
        }

        $form->add(
            Separator::make('field-layout-separator'),
            Field::make(null, FieldLayoutDesigner::make('fieldLayout')
                ->elementType(Asset::class)
                ->withGeneratedFields()
                ->withCardViewDesigner()),
        );

        return $this->formResolver->resolve($form, new FormContext(
            values: $values,
            errors: $this->volume->errors()->getMessages(),
            mode: $this->readOnly ? ControlMode::ReadOnly : ControlMode::Editable,
            refreshable: ! $this->readOnly,
        ));
    }

    /** @return array{method: 'post', url: string} */
    public function submit(): array
    {
        return [
            'method' => 'post',
            'url' => action([VolumesController::class, 'store']),
        ];
    }

    public function refreshUrl(): ?string
    {
        return $this->readOnly
            ? null
            : action([VolumesController::class, 'renderForm']);
    }

    /** @return array<string, mixed> */
    private function initialValues(): array
    {
        $fieldLayout = $this->volume->getFieldLayout();

        return [
            'volumeId' => $this->volume->id,
            'name' => $this->volume->name ?? '',
            'handle' => $this->volume->handle ?? '',
            'fsHandle' => $this->volume->getFsHandle(false) ?? '',
            'subpath' => $this->volume->getSubpath(ensureTrailing: false, parse: false),
            'transformFsHandle' => $this->volume->getTransformFsHandle(false) ?? '',
            'transformSubpath' => $this->volume->getTransformSubpath(ensureTrailing: false, parse: false),
            'titleTranslationMethod' => $this->volume->titleTranslationMethod->value,
            'titleTranslationKeyFormat' => $this->volume->titleTranslationKeyFormat ?? '',
            'altTranslationMethod' => $this->volume->altTranslationMethod->value,
            'altTranslationKeyFormat' => $this->volume->altTranslationKeyFormat ?? '',
            'fieldLayout' => [
                'id' => $fieldLayout->id,
                'uid' => $fieldLayout->uid,
                ...($fieldLayout->getConfig() ?? []),
            ],
        ];
    }
}

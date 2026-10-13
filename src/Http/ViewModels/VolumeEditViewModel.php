<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Asset\AssetTransformers;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Volumes;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\Http\Controllers\Settings\VolumesController;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Ui\Controls\Choice;
use CraftCms\Cms\Ui\Controls\Combobox;
use CraftCms\Cms\Ui\Controls\FieldLayoutDesigner;
use CraftCms\Cms\Ui\Controls\Handle;
use CraftCms\Cms\Ui\Controls\Lightswitch;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Nodes\Group;
use CraftCms\Cms\Ui\Nodes\HiddenField;
use CraftCms\Cms\Ui\Nodes\Separator;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiPayload;
use CraftCms\Cms\Ui\UiResolver;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class VolumeEditViewModel extends ViewModel
{
    /** @param array<string, mixed>|null $values */
    public function __construct(
        private readonly Volume $volume,
        private readonly Volumes $volumes,
        private readonly UiResolver $uiResolver,
        private readonly AssetTransformers $assetTransformers,
        private readonly bool $readOnly = false,
        private readonly ?array $values = null,
    ) {}

    public function ui(): UiPayload
    {
        $values = $this->values ?? $this->initialValues();
        $handle = Handle::make('handle');
        $objectTemplateTip = SelectOptions::getObjectTemplateTip();
        $objectTemplateTriggers = SelectOptions::getObjectTemplateTextExpanderTriggers(
            Asset::class,
            [$this->volume->getFieldLayout()],
        );
        $envTextExpanderTriggers = SelectOptions::getEnvTextExpanderTriggers();
        $disabledFilesystemTargets = $this->volumes->getAllVolumes()
            ->reject(fn (Volume $volume): bool => $volume->id === $this->volume->id || (bool) $volume->getSubpath())
            ->map(fn (Volume $volume): ?string => $volume->getResolvedFsTarget())
            ->filter(fn (?string $target): bool => $target !== null)
            ->values()
            ->all();

        if ($this->volume->id === null && empty($values['handle'])) {
            $handle->source('name');
        }

        $ui = Ui::make([
            HiddenField::make('volumeId'),
            Field::make(t('Name'), Text::make('name')->autofocus())->required(),
            Field::make(t('Handle'), $handle)->required(),
            Separator::make('filesystem-separator'),
            Field::make(
                t('Asset Disk'),
                Combobox::make('fsHandle')
                    ->options($this->diskOptions($disabledFilesystemTargets))
                    ->showAllOnEmpty()
                    ->showSelectedHint(),
            )
                ->instructions(t('Choose which Laravel filesystem disk assets should be stored in.'))
                ->tip(t('This can be set to an environment variable matching one of the option values.'))
                ->required(),
            Field::make(t('Assets in this volume have public URLs'), Lightswitch::make('hasUrls'))
                ->instructions(t('Whether Craft should generate public URLs for assets in this volume.')),
            Field::make(
                t('Subpath'),
                Text::make('subpath')->textExpanderTriggers($envTextExpanderTriggers),
            )
                ->instructions(t('Where assets should be stored on the filesystem.'))
                ->tip(t('Type `$` to choose an environment variable.')),
            Field::make(
                t('Asset Transformer'),
                Combobox::make('assetTransformer')
                    ->options($this->assetTransformerOptions())
                    ->showAllOnEmpty(),
            )->instructions(t('Select the Asset Transformer for this volume. Leave blank to use the global default.')),
        ]);

        if (Sites::isMultiSite()) {
            $ui->add(
                Separator::make('translation-separator'),
                Field::make(
                    t('{name} Translation Method', ['name' => t('Title')]),
                    Choice::make('titleTranslationMethod')->options(TranslationMethod::asOptions())->reactive(),
                )->instructions(t('How should {name} values be translated?', ['name' => t('Title')])),
            );

            if (($values['titleTranslationMethod'] ?? null) === TranslationMethod::Custom->value) {
                $ui->add(Group::make('volume-title-translation-settings', [
                    Field::make(
                        t('{name} Translation Key Format', ['name' => t('Title')]),
                        Text::make('titleTranslationKeyFormat')
                            ->monospace()
                            ->textExpanderTriggers($objectTemplateTriggers),
                    )
                        ->instructions(t('Template that defines the {name} field’s custom “translation key” format. Values will be copied to all sites that produce the same key.', [
                            'name' => t('Title'),
                        ]))
                        ->tip($objectTemplateTip),
                ])->dependsOn('titleTranslationMethod'));
            }

            $ui->add(Field::make(
                t('{name} Translation Method', ['name' => t('Alternative Text')]),
                Choice::make('altTranslationMethod')->options(TranslationMethod::asOptions())->reactive(),
            )->instructions(t('How should {name} values be translated?', ['name' => t('Alternative Text')])));

            if (($values['altTranslationMethod'] ?? null) === TranslationMethod::Custom->value) {
                $ui->add(Group::make('volume-alt-translation-settings', [
                    Field::make(
                        t('{name} Translation Key Format', ['name' => t('Alternative Text')]),
                        Text::make('altTranslationKeyFormat')
                            ->monospace()
                            ->textExpanderTriggers($objectTemplateTriggers),
                    )
                        ->instructions(t('Template that defines the {name} field’s custom “translation key” format. Values will be copied to all sites that produce the same key.', [
                            'name' => t('Alternative Text'),
                        ]))
                        ->tip($objectTemplateTip),
                ])->dependsOn('altTranslationMethod'));
            }
        }

        $ui->add(
            Separator::make('field-layout-separator'),
            Field::make(null, FieldLayoutDesigner::make('fieldLayout')
                ->elementType(Asset::class)
                ->withGeneratedFields()
                ->withCardViewDesigner()),
        );

        return $this->uiResolver->resolve($ui, new UiContext(
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
            : action([VolumesController::class, 'renderUi']);
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
            'hasUrls' => $this->volume->hasUrls,
            'subpath' => $this->volume->getSubpath(ensureTrailing: false, parse: false),
            'assetTransformer' => $this->volume->getAssetTransformerHandle(false) ?? '',
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

    /** @return list<array{value:string,label:string}> */
    private function assetTransformerOptions(): array
    {
        return $this->assetTransformers
            ->getAllAssetTransformers()
            ->map(fn ($transformer): array => [
                'value' => $transformer->handle,
                'label' => $transformer->name,
            ])
            ->sortBy('label')
            ->prepend(['value' => '', 'label' => t('Default')])
            ->concat(SelectOptions::getEnvSuggestions())
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $disabledTargets
     * @return list<array<string, mixed>>
     */
    private function diskOptions(array $disabledTargets): array
    {
        $options = collect(SelectOptions::getDiskOptions())
            ->map(fn (array $option): array => [
                ...$option,
                'disabled' => in_array($option['value'], $disabledTargets, true),
                'data' => ['hint' => $option['value']],
            ])
            ->prepend(['label' => t('Select a disk'), 'value' => '', 'disabled' => false, 'data' => ['hint' => '']])
            ->all();

        return [...$options, ...SelectOptions::getEnvOptions(collect($options)->pluck('value')->all())];
    }
}

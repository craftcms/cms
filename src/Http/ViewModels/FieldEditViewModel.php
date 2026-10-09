<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\MissingField;
use CraftCms\Cms\Http\Controllers\FieldsController;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Ui\Controls\Choice;
use CraftCms\Cms\Ui\Controls\Combobox;
use CraftCms\Cms\Ui\Controls\Handle;
use CraftCms\Cms\Ui\Controls\Lightswitch;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Controls\Textarea;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\Nodes\Field as UiField;
use CraftCms\Cms\Ui\Nodes\Group;
use CraftCms\Cms\Ui\Nodes\HiddenField;
use CraftCms\Cms\Ui\Nodes\Scope;
use CraftCms\Cms\Ui\Nodes\Separator;
use CraftCms\Cms\Ui\Nodes\TemplateContent;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiPayload;
use CraftCms\Cms\Ui\UiResolver;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class FieldEditViewModel extends ViewModel
{
    private ?Ui $settingsUi = null;

    private bool $settingsUiResolved = false;

    public function __construct(
        private readonly Field $field,
        private readonly Fields $fieldsService,
        private readonly bool $readOnly = false,
        private readonly bool $multiInstanceTypesOnly = false,
    ) {}

    public function ui(): UiPayload
    {
        $type = $this->typeClass();
        $translationMethods = $this->translationMethods(
            $this->field instanceof MissingField ? $this->field::class : $type,
        );
        $translationMethod = in_array($this->field->translationMethodValue, $translationMethods, true)
            ? $this->field->translationMethodValue
            : ($translationMethods[0] ?? TranslationMethod::None->value);
        $handle = Handle::make('handle');

        if (! $this->field->id && ! $this->field->handle) {
            $handle->source('name');
        }

        $nodes = [
            HiddenField::make('fieldId'),
            HiddenField::make('oldType')->mode(ControlMode::ReadOnly),
            UiField::make(t('Name'), Text::make('name')->autofocus())
                ->instructions(t('What this field will be called in the control panel.'))
                ->required(),
            UiField::make(t('Handle'), $handle)
                ->instructions(t('How you’ll refer to this field in the templates.'))
                ->required(),
            UiField::make(t('Default Instructions'), Textarea::make('instructions'))
                ->instructions(t('Helper text to guide the author.')),
            UiField::make(t('Use this field’s values as search keywords'), Lightswitch::make('searchable')),
        ];
        $typeField = UiField::make(t('Field Type'), Combobox::make('type')
            ->options($this->fieldTypeOptions())
            ->requireOptionMatch()
            ->reactive())
            ->instructions(t('What type of field is this?'))
            ->required();

        if ($this->field->id) {
            $typeField->warning(t('Changing this may result in data loss.'));
        }

        $nodes[] = $typeField;

        if ($this->field instanceof MissingField) {
            $nodes[] = TemplateContent::make('missing-field-placeholder', $this->field->getPlaceholderHtml());
        }

        $translationOptions = $this->translationMethodOptions($translationMethods);

        if (Sites::isMultiSite() && count($translationOptions) > 1) {
            $nodes[] = UiField::make(
                t('Translation Method'),
                Choice::make('translationMethod')->options($translationOptions)->reactive(),
            )->instructions(t('How should this field’s values be translated?'));

            if ($translationMethod === TranslationMethod::Custom->value) {
                $nodes[] = Group::make('field-translation-settings', [
                    UiField::make(
                        t('Translation Key Format'),
                        Text::make('translationKeyFormat')
                            ->monospace()
                            ->textExpanderTriggers(SelectOptions::getObjectTemplateTextExpanderTriggers()),
                    )
                        ->instructions(t('Template that defines the field’s custom “translation key” format. Field values will be copied to all sites that produce the same key.'))
                        ->tip(SelectOptions::getObjectTemplateTip()),
                ])->dependsOn('translationMethod');
            }
        } else {
            $nodes[] = HiddenField::make('translationMethod');
            $nodes[] = HiddenField::make('translationKeyFormat');
        }

        $nodes[] = Separator::make('field-settings-separator');
        $mode = $this->readOnly ? ControlMode::ReadOnly : ControlMode::Editable;
        $refreshable = ! $this->readOnly;
        $settingsUi = $this->settingsUiDefinition($this->settingsUiContext());

        if ($settingsUi !== null) {
            $nodes[] = Scope::make('settings', [
                Group::make('field-settings', $settingsUi->nodes())->dependsOn('type'),
            ]);
        }

        return app(UiResolver::class)->resolve(Ui::make($nodes), new UiContext(
            values: [
                'fieldId' => $this->field->id,
                'oldType' => $type,
                'type' => $type,
                'name' => $this->field->name ?? '',
                'handle' => $this->field->handle ?? '',
                'instructions' => $this->field->instructions ?? '',
                'searchable' => $this->field->searchable,
                'translationMethod' => $translationMethod,
                'translationKeyFormat' => $this->field->translationKeyFormat ?? '',
            ],
            mode: $mode,
            refreshable: $refreshable,
        ));
    }

    public function settingsUi(): ?UiPayload
    {
        $context = $this->settingsUiContext();
        $ui = $this->settingsUiDefinition($context);

        return $ui === null
            ? null
            : app(UiResolver::class)->resolve($ui, $context);
    }

    /** @return array{method: 'post', url: string} */
    public function submit(): array
    {
        return [
            'method' => 'post',
            'url' => action([FieldsController::class, 'store']),
        ];
    }

    public function refreshUrl(): ?string
    {
        if ($this->readOnly) {
            return null;
        }

        return action(
            [FieldsController::class, 'renderUi'],
            $this->multiInstanceTypesOnly ? ['multiInstanceTypesOnly' => 1] : [],
        );
    }

    /** @return array<class-string<FieldInterface>, string[]> */
    public function supportedTranslationMethods(): array
    {
        $currentType = $this->typeClass();

        return $this->fieldsService->getAllFieldTypes()
            ->filter(fn (string $class): bool => $class === $currentType || (
                $class::isSelectable() &&
                (! $this->multiInstanceTypesOnly || $class::isMultiInstance())
            ))
            ->mapWithKeys(fn (string $class): array => [$class => $this->translationMethods($class)])
            ->all();
    }

    /** @return list<array{value: class-string<FieldInterface>, label: string}> */
    private function fieldTypeOptions(): array
    {
        $allFieldTypes = $this->fieldsService->getAllFieldTypes();
        $savedField = $this->field->id
            ? $this->fieldsService->getFieldById($this->field->id)
            : null;
        $compatibleFieldTypes = $savedField
            ? $this->fieldsService->getCompatibleFieldTypes($savedField, includeCurrent: true)
            : $allFieldTypes;
        $currentType = $this->typeClass();
        $options = $allFieldTypes
            ->filter(fn (string $class): bool => $class === $currentType || (
                $class::isSelectable() &&
                (! $this->multiInstanceTypesOnly || $class::isMultiInstance())
            ))
            ->map(function (string $class) use ($compatibleFieldTypes, $currentType): array {
                $name = $class::displayName();
                $icon = Icons::resolveIconData(
                    $class === $currentType && ! $this->field instanceof MissingField
                        ? $this->field->getIcon()
                        : $class::icon(),
                );

                return [
                    'data' => [
                        'icon' => $icon['family'] === 'solid'
                            ? $icon['name']
                            : "{$icon['family']}/{$icon['name']}",
                    ],
                    'value' => $class,
                    'label' => $class === $currentType || $compatibleFieldTypes->contains($class)
                        ? $name
                        : "$name ⚠",
                ];
            })
            ->sortBy('label')
            ->values()
            ->all();

        if ($this->field instanceof MissingField && ! in_array($currentType, array_column($options, 'value'), true)) {
            array_unshift($options, ['value' => $currentType, 'label' => '']);
        }

        return $options;
    }

    /**
     * @param  list<string>  $supported
     * @return list<array{value: string, label: string}>
     */
    private function translationMethodOptions(array $supported): array
    {
        return array_values(array_filter([
            ['value' => TranslationMethod::None->value, 'label' => t('Not translatable')],
            ['value' => TranslationMethod::Site->value, 'label' => t('Translate for each site')],
            ['value' => TranslationMethod::SiteGroup->value, 'label' => t('Translate for each site group')],
            ['value' => TranslationMethod::Language->value, 'label' => t('Translate for each language')],
            ['value' => TranslationMethod::Custom->value, 'label' => t('Custom…')],
        ], fn (array $option): bool => in_array($option['value'], $supported, true)));
    }

    /** @return list<string> */
    private function translationMethods(string $type): array
    {
        return array_map(
            static fn (TranslationMethod $method): string => $method->value,
            $type::supportedTranslationMethods(),
        );
    }

    private function settingsUiDefinition(UiContext $context): ?Ui
    {
        if (! $this->settingsUiResolved) {
            $this->settingsUi = $this->field->settingsUi($context);
            $this->settingsUiResolved = true;
        }

        return $this->settingsUi;
    }

    private function settingsUiContext(): UiContext
    {
        return new UiContext(
            namespace: 'settings',
            mode: $this->readOnly ? ControlMode::ReadOnly : ControlMode::Editable,
            refreshable: ! $this->readOnly,
        );
    }

    /**
     * @return class-string<FieldInterface> The type the field presents as — a missing
     *                                      field's expected type rather than MissingField itself.
     */
    private function typeClass(): string
    {
        return $this->field instanceof MissingField
            ? $this->field->expectedType
            : $this->field::class;
    }
}

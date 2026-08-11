<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Cp\Html\FieldHtml;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\MissingField;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Html;

use function CraftCms\Cms\t;

class FieldEditViewModel extends ViewModel
{
    private ?Form $settingsForm = null;

    private bool $settingsFormResolved = false;

    public function __construct(
        private readonly Field $field,
        private readonly Fields $fieldsService,
        private readonly bool $readOnly = false,
    ) {}

    /** @return array{
     *     id: int|null,
     *     name: string|null,
     *     handle: string|null,
     *     instructions: string|null,
     *     searchable: bool,
     *     type: class-string<FieldInterface>,
     *     translationMethod: string,
     *     translationKeyFormat: string|null,
     * }
     */
    public function field(): array
    {
        return [
            'id' => $this->field->id,
            'name' => $this->field->name,
            'handle' => $this->field->handle,
            'instructions' => $this->field->instructions,
            'searchable' => $this->field->searchable,
            'type' => $this->typeClass(),
            'translationMethod' => $this->field->translationMethodValue,
            'translationKeyFormat' => $this->field->translationKeyFormat,
        ];
    }

    public function brandNew(): bool
    {
        return ! $this->field->id;
    }

    public function readOnly(): bool
    {
        return $this->readOnly;
    }

    /** @return array<int, array{
     *     value: class-string<FieldInterface>,
     *     label: string,
     *     icon: string|null,
     *     id: string,
     *     compatible: bool,
     * }>
     */
    public function fieldTypeOptions(): array
    {
        $allFieldTypes = $this->fieldsService->getAllFieldTypes();

        $compatibleFieldTypes = $this->field->id
            ? $this->fieldsService->getCompatibleFieldTypes($this->field, includeCurrent: true)
            : $allFieldTypes;

        $currentType = $this->typeClass();

        $options = [];
        $names = [];

        foreach ($allFieldTypes as $class) {
            $isCurrent = $class === $currentType;

            if (! $isCurrent && ! $class::isSelectable()) {
                continue;
            }

            $names[] = $name = $class::displayName();
            $options[] = [
                'value' => $class,
                'label' => $name,
                'icon' => $isCurrent ? $this->field->getIcon() : $class::icon(),
                'id' => Html::id($class),
                'compatible' => $isCurrent || $compatibleFieldTypes->contains($class),
            ];
        }

        array_multisort($names, $options);

        // A missing type still needs a selectable (blank) option so the select
        // reflects it until the user picks a real type
        if ($this->field instanceof MissingField && ! in_array($currentType, array_column($options, 'value'), true)) {
            array_unshift($options, [
                'value' => $currentType,
                'label' => '',
                'icon' => null,
                'id' => Html::id($currentType),
                'compatible' => true,
            ]);
        }

        return $options;
    }

    public function missingFieldPlaceholder(): ?string
    {
        return $this->field instanceof MissingField
            ? $this->field->getPlaceholderHtml()
            : null;
    }

    public function metadataHtml(): ?string
    {
        return $this->field->id
            ? app(FieldHtml::class)->metadataHtml($this->field)
            : null;
    }

    /** @return array<class-string<FieldInterface>, string[]> */
    public function supportedTranslationMethods(): array
    {
        $currentType = $this->typeClass();

        return $this->fieldsService->getAllFieldTypes()
            ->filter(fn (string $class): bool => $class === $currentType || $class::isSelectable())
            ->mapWithKeys(fn (string $class): array => [
                $class => array_map(
                    static fn (TranslationMethod $method): string => $method->value,
                    $class::supportedTranslationMethods(),
                ),
            ])
            ->all();
    }

    /** @return array<int, array{value: string, label: string}> */
    public function translationMethodOptions(): array
    {
        return [
            ['value' => TranslationMethod::None->value, 'label' => t('Not translatable')],
            ['value' => TranslationMethod::Site->value, 'label' => t('Translate for each site')],
            ['value' => TranslationMethod::SiteGroup->value, 'label' => t('Translate for each site group')],
            ['value' => TranslationMethod::Language->value, 'label' => t('Translate for each language')],
            ['value' => TranslationMethod::Custom->value, 'label' => t('Custom…')],
        ];
    }

    public function isMultiSite(): bool
    {
        return Sites::isMultiSite();
    }

    public function settingsForm(): ?FormPayload
    {
        $form = $this->settingsFormDefinition();

        if ($form === null) {
            return null;
        }

        return app(FormResolver::class)->resolve($form, $this->settingsFormContext());
    }

    private function settingsFormDefinition(): ?Form
    {
        if (! $this->settingsFormResolved) {
            $this->settingsForm = $this->field->settingsForm($this->settingsFormContext());
            $this->settingsFormResolved = true;
        }

        return $this->settingsForm;
    }

    private function settingsFormContext(): FormContext
    {
        return new FormContext(
            namespace: 'settings',
            mode: $this->readOnly ? ControlMode::ReadOnly : ControlMode::Editable,
            refreshable: true,
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

<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Component\Contracts\Iconic;
use CraftCms\Cms\Cp\Html\FieldHtml;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\MissingField;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;

use function CraftCms\Cms\t;

class FieldEditViewModel extends ViewModel
{
    public function __construct(
        private readonly FieldInterface $field,
        private readonly Fields $fieldsService,
        private readonly bool $readOnly = false,
        private readonly bool $embedded = false,
        private readonly bool $multiInstanceTypesOnly = false,
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
            'translationMethod' => $this->field->translationMethod,
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

    public function embedded(): bool
    {
        return $this->embedded;
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

            if (
                ! $isCurrent &&
                (! $class::isSelectable() || ($this->multiInstanceTypesOnly && ! $class::isMultiInstance()))
            ) {
                continue;
            }

            $names[] = $name = $class::displayName();
            $options[] = [
                'value' => $class,
                'label' => $name,
                'icon' => $isCurrent && $this->field instanceof Iconic
                    ? $this->field->getIcon()
                    : $class::icon(),
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

    public function settingsInputNamespace(): string
    {
        return sprintf('types[%s]', $this->typeId());
    }

    public function settingsBindingScope(): string
    {
        return sprintf('types.%s', $this->typeId());
    }

    /** @return array{elements: list<array<string, mixed>>}|null */
    public function settingsForm(): ?array
    {
        return InputNamespace::with(
            $this->settingsInputNamespace(),
            fn (): ?array => $this->field->getSettingsForm($this->readOnly)?->toArray(),
        );
    }

    /** @return array{types: array<string, array<string, mixed>>} */
    public function settingsValues(): array
    {
        $settings = $this->field instanceof MissingField
            ? $this->field->settings ?? []
            : $this->field->getSettings();

        return [
            'types' => [
                $this->typeId() => $settings,
            ],
        ];
    }

    /** @return array<string, string[]> */
    public function settingsErrors(): array
    {
        $settingsAttributes = array_flip($this->field->settingsAttributes());
        $errors = [];

        foreach ($this->field->errors()->getMessages() as $attribute => $messages) {
            $path = isset($settingsAttributes[Str::before($attribute, '.')])
                ? sprintf('%s.%s', $this->settingsBindingScope(), $attribute)
                : $attribute;
            $errors[$path] = $messages;
        }

        return $errors;
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

    private function typeId(): string
    {
        return Html::id($this->typeClass());
    }
}

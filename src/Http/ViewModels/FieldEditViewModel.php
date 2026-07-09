<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Cp\Html\FieldHtml;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\MissingField;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\View\HtmlFragment;
use CraftCms\Cms\View\TemplateMode;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

class FieldEditViewModel extends ViewModel
{
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

    /**
     * The current field type's settings, rendered as a legacy HTML island.
     * Inputs are namespaced `types[<typeId>]` to match what the save and
     * render-settings endpoints expect.
     */
    public function settings(): HtmlFragment
    {
        return HtmlStack::capture(fn (): string => template('settings/fields/_type-settings', [
            'field' => $this->field,
            'namespace' => sprintf('types[%s]', Html::id($this->typeClass())),
            'readOnly' => $this->readOnly,
        ], templateMode: TemplateMode::Cp));
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

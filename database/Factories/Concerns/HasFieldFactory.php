<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Factories\Concerns;

use CraftCms\Cms\Database\Factories\ElementFactoryResult;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Tests\TestClasses\Factory\FactoryFieldConfig;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;

/**
 * Trait for element factories that need to create elements with custom fields.
 *
 * @mixin Factory
 */
trait HasFieldFactory
{
    /** @var array<FactoryFieldConfig> */
    protected array $fieldConfigs = [];

    protected ?string $elementScenario = null;

    /**
     * Add a custom field to the element's field layout.
     */
    public function withField(
        string $handle,
        string $type,
        array $settings = [],
        bool $required = false,
        mixed $value = null,
    ): static {
        $clone = clone $this;
        $clone->fieldConfigs = [
            ...$this->fieldConfigs,
            new FactoryFieldConfig(
                handle: $handle,
                type: $type,
                settings: $settings,
                required: $required,
                value: $value,
            ),
        ];

        return $clone;
    }

    /**
     * Set the element scenario for validation.
     */
    public function withScenario(string $scenario): static
    {
        $clone = clone $this;
        $clone->elementScenario = $scenario;

        return $clone;
    }

    /**
     * Create the element with configured fields and return a result object.
     */
    public function createElementWithFields(array $attributes = []): ElementFactoryResult
    {
        /** @var Collection<string, Field> $fields */
        $fields = collect();

        if (empty($this->fieldConfigs)) {
            return new ElementFactoryResult(
                element: $this->createElement($attributes),
                fields: $fields,
            );
        }

        // Create all fields
        foreach ($this->fieldConfigs as $config) {
            $field = Field::factory()->create([
                'name' => Str::title($config->handle),
                'handle' => $config->handle,
                'type' => $config->type,
                'settings' => $config->settings,
            ]);
            $fields->put($config->handle, $field);
        }

        // Create field layout with all fields
        $fieldLayout = FieldLayout::create([
            'type' => $this->getElementClass(),
            'config' => $this->buildFieldLayoutConfig($fields),
        ]);

        // Create the model and link field layout
        $model = $this->create($attributes);
        $this->attachFieldLayoutToModel($model, $fieldLayout);

        // Refresh caches
        $this->refreshFieldCaches();

        // Query for the element
        $element = $this->queryElement($model->id);
        $element->setScenario($this->elementScenario ?? Element::SCENARIO_DEFAULT);
        $element->title = $element->title ?: 'Test entry';

        // Set field values
        foreach ($this->fieldConfigs as $config) {
            if ($config->value !== null) {
                $element->setFieldValue($config->handle, $config->value);
            }
        }

        return new ElementFactoryResult(
            element: $element,
            fields: $fields,
        );
    }

    /**
     * Build the field layout config for all fields.
     *
     * @param  Collection<string, Field>  $fields
     */
    protected function buildFieldLayoutConfig(Collection $fields): array
    {
        $elements = [];

        foreach ($this->fieldConfigs as $config) {
            $field = $fields->get($config->handle);
            if ($field) {
                $elements[] = [
                    'uid' => Str::uuid()->toString(),
                    'type' => CustomField::class,
                    'fieldUid' => $field->uid,
                    'required' => $config->required,
                ];
            }
        }

        return [
            'tabs' => [
                [
                    'uid' => Str::uuid()->toString(),
                    'name' => 'Content',
                    'elements' => $elements,
                ],
            ],
        ];
    }

    /**
     * Refresh global caches after creating fields.
     */
    protected function refreshFieldCaches(): void
    {
        EntryTypes::refreshEntryTypes();
        Fields::refreshFields();
    }

    /**
     * Get the element class for this factory.
     */
    abstract protected function getElementClass(): string;

    /**
     * Attach the field layout to the created model.
     */
    abstract protected function attachFieldLayoutToModel(mixed $model, FieldLayout $fieldLayout): void;

    /**
     * Query for the element by ID.
     */
    abstract protected function queryElement(int $id): Element;
}

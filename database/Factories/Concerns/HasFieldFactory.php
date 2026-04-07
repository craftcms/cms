<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Factories\Concerns;

use CraftCms\Cms\Database\Factories\ElementFactoryResult;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Tests\TestClasses\Factory\FactoryFieldConfig;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;
use Override;

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
     *
     * When `$save` is true (the default), the element is saved via `saveElement()`
     * and re-queried from the database, ensuring field values are persisted.
     */
    public function createElementWithFields(array $attributes = [], bool $save = true): ElementFactoryResult
    {
        $factory = $this->extractElementAttributes($attributes);

        /** @var Collection<string, Field> $fields */
        $fields = collect();

        if (empty($factory->fieldConfigs)) {
            return new ElementFactoryResult(
                element: $factory->createElement($attributes),
                fields: $fields,
            );
        }

        foreach ($factory->fieldConfigs as $config) {
            $field = Field::factory()->create([
                'name' => Str::title($config->handle),
                'handle' => $config->handle,
                'type' => $config->type,
                'settings' => $config->settings,
            ]);
            $fields->put($config->handle, $field);
        }

        $fieldLayout = FieldLayout::create([
            'type' => $factory->getElementClass(),
            'config' => $factory->buildFieldLayoutConfig($fields),
        ]);

        $model = $factory->create($attributes);
        $factory->attachFieldLayoutToModel($model, $fieldLayout);

        $factory->refreshFieldCaches();

        $element = $factory->queryElement($model->id);
        $element->setScenario($factory->elementScenario ?? Element::SCENARIO_DEFAULT);
        $element->title = $element->title ?: 'Test entry';

        foreach ($factory->fieldConfigs as $config) {
            if ($config->value !== null) {
                $element->setFieldValue($config->handle, $config->value);
            }
        }

        if ($save) {
            Elements::saveElement($element);
            $element = $factory->queryElement($model->id);
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
     * Extract title and slug from attributes and apply them as factory state.
     *
     * Returns a new factory instance with the state applied. The attributes
     * array is modified by reference to remove the extracted keys.
     */
    protected function extractElementAttributes(array &$attributes): static
    {
        $factory = clone $this;

        if (Arr::has($attributes, 'title')) {
            $factory = $factory->title(Arr::pull($attributes, 'title'));
        }

        if (Arr::has($attributes, 'slug')) {
            return $factory->slug(Arr::pull($attributes, 'slug'));
        }

        return $factory;
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    #[Override]
    protected function newInstance(array $arguments = []): static
    {
        $instance = parent::newInstance($arguments);
        $instance->fieldConfigs = $this->fieldConfigs;
        $instance->elementScenario = $this->elementScenario;

        return $instance;
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

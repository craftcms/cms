# Form Element renderers

Plugins can add controls to native Forms by pairing a PHP CP UI Component with a Vue renderer. Both use the same Form Element Type, such as `color-tools:color-map`.

Form Element Types must be lowercase, namespaced identifiers. The `craft` namespace is reserved. Treat the type as public API and do not rename it in a compatible release.

## Define the PHP component

The component must extend `ViewComponent` and implement `FormElement`. Input controls can extend `ScalarInput`, which provides the common input and projection behavior.

`formElementType()` identifies the serialized Form Element and its Vue renderer. `tagName()` identifies the HTML element rendered by PHP and Twig. Both are required because multiple Form Element Types can use the same web component; for example, text, number, date, and time inputs all render `<craft-input>`.

```php
use CraftCms\Cms\Cp\Components\ScalarInput;
use CraftCms\Cms\Support\Json;
use Override;

class ColorMap extends ScalarInput
{
    /** @var list<string> */
    private array $colors = [];

    public static function formElementType(): string
    {
        return 'color-tools:color-map';
    }

    /** @param list<string> $colors */
    public function colors(array $colors): static
    {
        $this->colors = $colors;

        return $this;
    }

    #[Override]
    protected function tagName(): string
    {
        return 'color-tools-map';
    }

    #[Override]
    protected function hostAttributes(): array
    {
        return [
            ...parent::hostAttributes(),
            'colors' => Json::encode($this->colors),
        ];
    }

    #[Override]
    protected function formElementProps(): array
    {
        return ['colors' => $this->colors];
    }
}
```

Ship the web component returned by `tagName()` so the control also works outside Vue. `formElementProps()` defines the values passed to the Vue renderer.

Register the component from your plugin:

```php
$this->registerFormElementTypes(ColorMap::class);
```

Craft records the plugin as the type’s owner and rejects conflicting registrations.

## Register the Vue renderer

Register the renderer from your plugin’s control-panel JavaScript using its Form Element Type. Registration can be eager or lazy.

```ts
import ColorMap from './components/ColorMap.vue'

Cp.$formElements.register('color-tools:color-map', ColorMap)

Cp.$formElements.register(
  'color-tools:gradient',
  () => import('./components/Gradient.vue'),
)
```

PHP registration does not register or load the Vue component. Your plugin’s control-panel assets must perform this step.

The renderer receives:

- Properties returned by `formElementProps()`.
- Resolved HTML attributes, including `name`, `id`, required state, and accessibility attributes.
- The current value as `modelValue` for value-bound elements.
- The effective `readonly` state.

Emit `update:modelValue` when the value changes:

```vue
<script setup lang="ts">
  defineProps<{
    colors?: string[]
    modelValue?: string
    readonly?: boolean
  }>()

  defineEmits<{ 'update:modelValue': [value: string] }>()
</script>
```

Forward applicable HTML attributes to the renderer’s actual form control. Craft owns binding scopes, input names, IDs, values, validation errors, visibility, read-only state, and persistence.

Use a generated `@craftcms/ui` Vue wrapper directly when its value and properties already match your Form Element. Add a custom renderer only when additional Vue-specific behavior is required.

## Use the component

Wrap input components in `Field` to provide their label, instructions, width, validation presentation, visibility, and read-only state.

```php
use CraftCms\Cms\Cp\Components\Field;
use CraftCms\Cms\Cp\Forms\Form;

Form::make([
    Field::make()
        ->label('Palette')
        ->input(
            ColorMap::make()
                ->name('palette')
                ->colors(['red', 'blue']),
        ),
]);
```

Container components can extend `FormContainer`. Craft passes their rendered children to the registered Vue renderer’s default slot.

## Missing renderers

If a plugin-owned renderer is unavailable, Craft displays the Form Element Type and plugin ownership details. If a registered renderer throws, Craft displays a separate failed-renderer diagnostic.

## Legacy settings

The Yii 2 adapter’s Legacy Settings component exists only for backward compatibility. It is not a native Form Element API or a general raw-HTML renderer. New and migrated plugins should provide a native component and renderer.
